<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Services\RoomOperationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\Audit;
use Illuminate\Validation\Rule;

/** The hotel representative's page: mark rooms as occupied or checked out. */
class RepresentativeController extends Controller
{
    public function __construct(private RoomOperationService $operations)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        // A representative only ever sees the hotel assigned to the account.
        $hotels = $user->role === 'representative'
            ? Hotel::where('id', $user->hotel_id)->where('is_active', true)->get()
            : Hotel::where('is_active', true)->orderBy('city')->orderBy('name')->get();

        $hotel = $hotels->firstWhere('id', $request->integer('hotel')) ?? $hotels->first();

        $requested = (string) $request->query('date');
        $day = ($requested !== '' && strtotime($requested)) ? Carbon::parse($requested)->toDateString() : today()->toDateString();

        $bookings = collect();
        $stats = ['expected' => 0, 'in_house' => 0, 'departing' => 0, 'overdue' => 0];

        if ($hotel) {
            $occupiedRooms = fn ($q) => $q->where('booking_room.operational_status', 'occupied');

            $bookings = Booking::where('hotel_id', $hotel->id)
                ->where('status', Booking::STATUS_CONFIRMED)
                ->where(function ($q) use ($day, $occupiedRooms) {
                    $q->where(fn ($w) => $w->where('check_in', '<=', $day)->where('check_out', '>=', $day))
                      ->orWhere(fn ($w) => $w->where('check_out', '<', $day)->whereHas('rooms', $occupiedRooms));
                })
                ->with([
                    'company:id,name',
                    'rooms' => fn ($q) => $q->orderBy('rooms.room_number'),
                ])
                ->orderBy('check_in')
                ->orderBy('id')
                ->get();

            foreach ($bookings as $booking) {
                $checkIn = $booking->check_in->toDateString();
                $checkOut = $booking->check_out->toDateString();

                foreach ($booking->rooms as $room) {
                    $state = $room->pivot->operational_status;

                    if ($state === 'booked' && $checkIn === $day) {
                        $stats['expected']++;
                    }
                    if ($state === 'occupied') {
                        $stats['in_house']++;
                        if ($checkOut === $day) {
                            $stats['departing']++;
                        } elseif ($checkOut < $day) {
                            $stats['overdue']++;
                        }
                    }
                }
            }
        }

        return view('representative.index', [
            'hotels'   => $hotels,
            'hotel'    => $hotel,
            'day'      => $day,
            'bookings' => $bookings,
            'stats'    => $stats,
            'assigned' => $user->role !== 'representative' || $user->hotel_id !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'action'     => ['required', Rule::in([RoomOperationService::OCCUPIED, RoomOperationService::CHECKED_OUT, RoomOperationService::RESET])],
            'rooms'      => ['required', 'array', 'min:1', 'max:200'],
            'rooms.*'    => ['integer'],
        ], [
            'rooms.required' => 'Select at least one room first.',
            'rooms.min'      => 'Select at least one room first.',
        ]);

        $booking = Booking::findOrFail($data['booking_id']);
        $user = $request->user();

        // A representative may only change rooms of their own hotel.
        abort_if($user->role === 'representative' && (int) $user->hotel_id !== (int) $booking->hotel_id, 403);

        $changed = $this->operations->mark($booking, array_map('intval', $data['rooms']), $data['action'], $user);

        Audit::record('rooms.marked', [
            'by' => $user->email, 'booking' => $booking->id, 'action' => $data['action'], 'rooms' => $changed,
        ]);

        if ($changed === 0) {
            return back()->with('error', 'Nothing changed. The selected rooms were already in that state, or that step is not allowed for them.');
        }

        $label = ['occupied' => 'occupied', 'checked_out' => 'checked out', 'reset' => 'reset to booked'][$data['action']];

        return back()->with('success', $changed.' room(s) marked '.$label.'.');
    }
}
