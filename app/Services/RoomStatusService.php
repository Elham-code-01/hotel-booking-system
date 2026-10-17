<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hotel;
use Carbon\Carbon;

/** The single place that decides what state a room is in on a given day. */
class RoomStatusService
{
    /**
     * Rooms that are not in the result are vacant.
     *
     * @return array<int, array{status: string, company: string}> keyed by room id.
     *         status: occupied | arriving | departing | turnover | double
     */
    public function forHotel(Hotel $hotel, Carbon $date): array
    {
        $day = $date->toDateString();

        $bookings = Booking::where('hotel_id', $hotel->id)
            ->where('status', Booking::STATUS_CONFIRMED)
            ->where('check_in', '<=', $day)
            ->where('check_out', '>=', $day)
            ->with(['company:id,name', 'rooms:id'])
            ->get();

        $found = [];
        foreach ($bookings as $booking) {
            $kind = $booking->check_in->toDateString() === $day
                ? 'arriving'
                : ($booking->check_out->toDateString() === $day ? 'departing' : 'occupied');

            foreach ($booking->rooms as $room) {
                $found[$room->id][] = ['kind' => $kind, 'company' => $booking->company->name];
            }
        }

        $result = [];
        foreach ($found as $roomId => $items) {
            $kinds = array_column($items, 'kind');

            if (count($items) === 1) {
                $status = $kinds[0];
            } elseif (count($items) === 2 && in_array('arriving', $kinds, true) && in_array('departing', $kinds, true)) {
                $status = 'turnover';
            } else {
                $status = 'double';
            }

            $result[$roomId] = ['status' => $status, 'company' => implode(' / ', array_unique(array_column($items, 'company')))];
        }

        return $result;
    }
}
