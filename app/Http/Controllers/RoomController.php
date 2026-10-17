<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    /** Adds a range of rooms (for example 101 to 107). Rooms that already exist are skipped. */
    public function store(Request $request, Hotel $hotel): RedirectResponse
    {
        $data = $request->validate([
            'floor' => ['required', 'integer', 'between:0,30'],
            'from'  => ['required', 'integer', 'between:1,9999'],
            'to'    => ['required', 'integer', 'gte:from', 'max:9999'],
            'beds'  => ['required', 'integer', 'between:1,12'],
        ]);

        if ($data['to'] - $data['from'] >= 200) {
            return back()->withErrors(['to' => 'Add at most 200 rooms at once.'])->withInput();
        }

        $created = 0;
        for ($number = (int) $data['from']; $number <= (int) $data['to']; $number++) {
            $room = $hotel->rooms()->firstOrCreate(
                ['room_number' => (string) $number],
                ['floor' => $data['floor'], 'beds' => $data['beds']]
            );
            if ($room->wasRecentlyCreated) {
                $created++;
            }
        }

        Log::info('rooms.added', ['by' => $request->user()->email, 'hotel' => $hotel->id, 'count' => $created]);

        return back()->with('success', $created.' room(s) added. Existing numbers were skipped.');
    }

    /** Rooms are never deleted (history stays correct). They are made inactive instead. */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'floor' => ['required', 'integer', 'between:0,30'],
            'beds'  => ['required', 'integer', 'between:1,12'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $hasCurrentBookings = $room->bookings()
            ->where('bookings.status', Booking::STATUS_CONFIRMED)
            ->where('bookings.check_out', '>=', today())
            ->exists();

        if (! $data['is_active'] && $hasCurrentBookings) {
            return back()->with('error', 'Room '.$room->room_number.' has current or upcoming confirmed bookings, so it cannot be made inactive.');
        }

        $room->update($data);

        return back()->with('success', 'Room '.$room->room_number.' saved.');
    }
}
