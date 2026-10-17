<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single place that changes a room's day-to-day state inside a booking:
 * booked -> occupied -> checked_out   (and "reset" to undo a mistake).
 */
class RoomOperationService
{
    public const OCCUPIED = 'occupied';
    public const CHECKED_OUT = 'checked_out';
    public const RESET = 'reset';

    /**
     * @param  array<int, int>  $roomIds
     * @return int  how many rooms really changed
     */
    public function mark(Booking $booking, array $roomIds, string $action, User $by): int
    {
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw ValidationException::withMessages(['rooms' => 'Only confirmed bookings can be updated.']);
        }

        if ($action === self::OCCUPIED && $booking->check_in->toDateString() > today()->toDateString()) {
            throw ValidationException::withMessages([
                'rooms' => 'This booking starts on '.$booking->check_in->toDateString().'. Rooms cannot be marked occupied before that day.',
            ]);
        }

        $roomIds = array_values(array_unique($roomIds));

        return DB::transaction(function () use ($booking, $roomIds, $action, $by) {
            $rows = DB::table('booking_room')
                ->where('booking_id', $booking->id)
                ->whereIn('room_id', $roomIds)
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== count($roomIds)) {
                throw ValidationException::withMessages(['rooms' => 'One or more rooms do not belong to this booking.']);
            }

            $changed = 0;
            foreach ($rows as $row) {
                $current = $row->operational_status;

                $new = match ($action) {
                    self::OCCUPIED    => $current === 'booked' ? 'occupied' : null,
                    self::CHECKED_OUT => $current === 'occupied' ? 'checked_out' : null,
                    self::RESET       => $current !== 'booked' ? 'booked' : null,
                    default           => null,
                };

                if ($new === null) {
                    continue;
                }

                DB::table('booking_room')->where('id', $row->id)->update([
                    'operational_status' => $new,
                    'occupied_at'        => $new === 'occupied' ? now() : ($new === 'booked' ? null : $row->occupied_at),
                    'checked_out_at'     => $new === 'checked_out' ? now() : null,
                    'marked_by'          => $by->id,
                    'updated_at'         => now(),
                ]);
                $changed++;
            }

            return $changed;
        });
    }
}
