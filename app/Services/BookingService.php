<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All room-availability rules live here.
 * Only CONFIRMED bookings take rooms. Tentative and cancelled ones never block anything.
 */
class BookingService
{
    /** Active rooms of the hotel that no confirmed booking uses between $from and $to. */
    public function freeRooms(Hotel $hotel, string $from, string $to, ?int $ignoreBookingId = null): Collection
    {
        $busy = $this->busyRoomIds($hotel->id, $from, $to, $ignoreBookingId);

        return Room::where('hotel_id', $hotel->id)
            ->where('is_active', true)
            ->whereNotIn('id', $busy)
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();
    }

    /** @return array<int, int> */
    private function busyRoomIds(int $hotelId, string $from, string $to, ?int $ignoreBookingId, bool $lock = false): array
    {
        $query = DB::table('booking_room')
            ->join('bookings', 'bookings.id', '=', 'booking_room.booking_id')
            ->where('bookings.hotel_id', $hotelId)
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->where('bookings.check_in', '<', $to)
            ->where('bookings.check_out', '>', $from)
            ->when($ignoreBookingId, fn ($q) => $q->where('bookings.id', '!=', $ignoreBookingId));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->pluck('booking_room.room_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Creates or updates a booking and its rooms in one safe step.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $roomIds
     */
    public function save(array $data, array $roomIds, ?Booking $booking, User $by): Booking
    {
        return DB::transaction(function () use ($data, $roomIds, $booking, $by) {
            $roomIds = array_values(array_unique($roomIds));

            // Lock the chosen rooms first: two people cannot book the same room at the same moment.
            $rooms = Room::where('hotel_id', $data['hotel_id'])
                ->where('is_active', true)
                ->whereIn('id', $roomIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rooms->count() !== count($roomIds)) {
                throw ValidationException::withMessages([
                    'rooms' => 'One or more of the chosen rooms do not belong to this hotel or are not active.',
                ]);
            }

            if ($data['status'] === Booking::STATUS_CONFIRMED) {
                $busy = array_intersect(
                    $rooms->pluck('id')->all(),
                    $this->busyRoomIds((int) $data['hotel_id'], $data['check_in'], $data['check_out'], $booking?->id, true)
                );

                if ($busy) {
                    $numbers = Room::whereIn('id', $busy)->orderBy('room_number')->pluck('room_number')->join(', ');

                    throw ValidationException::withMessages([
                        'rooms' => 'These rooms are already taken for these dates: '.$numbers.'.',
                    ]);
                }
            }

            if ($booking !== null) {
                // Rooms that already have guests (or had guests) must stay in the booking.
                $inUse = DB::table('booking_room')
                    ->where('booking_id', $booking->id)
                    ->where('operational_status', '!=', 'booked')
                    ->get(['room_id', 'operational_status']);

                $removed = $inUse->pluck('room_id')->diff($rooms->pluck('id'));

                if ($removed->isNotEmpty()) {
                    $numbers = Room::whereIn('id', $removed->all())->orderBy('room_number')->pluck('room_number')->join(', ');

                    throw ValidationException::withMessages([
                        'rooms' => 'These rooms already have guests (or had guests) and cannot be removed: '.$numbers.'.',
                    ]);
                }

                if ($data['status'] === Booking::STATUS_CANCELLED && $inUse->where('operational_status', 'occupied')->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'rooms' => 'Guests are still in the hotel. Check them out first, then cancel the booking.',
                    ]);
                }
            }

            if ($booking === null) {
                $booking = new Booking($data);
                $booking->forceFill([
                    'exchange_rate' => (float) Setting::get('exchange_rate', '3.75'),
                    'created_by'    => $by->id,
                ]);
            } else {
                $booking->fill($data);
            }

            $booking->save();
            $booking->rooms()->sync($rooms->pluck('id')->all());

            return $booking;
        });
    }
}
