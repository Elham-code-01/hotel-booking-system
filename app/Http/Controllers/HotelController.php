<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Services\RoomStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** The Hotels page: choose a location, then see everything about its hotel. */
class HotelController extends Controller
{
    public function __construct(private RoomStatusService $status)
    {
    }

    public function index(Request $request): View
    {
        $hotels = Hotel::where('is_active', true)->orderBy('city')->orderBy('name')->get();
        $locations = $hotels->groupBy('city');

        $requested = (string) $request->query('location');
        $location = $locations->has($requested) ? $requested : $locations->keys()->first();

        $inLocation = $location !== null ? $locations[$location] : collect();
        $hotel = $inLocation->firstWhere('id', $request->integer('hotel')) ?? $inLocation->first();

        return view('hotels.index', [
            'locations'  => $locations,
            'location'   => $location,
            'inLocation' => $inLocation,
            'hotel'      => $hotel,
            'overview'   => $hotel ? $this->overview($hotel) : null,
            'isAdmin'    => $request->user()->role === 'admin',
            'inactive'   => $request->user()->role === 'admin' ? Hotel::where('is_active', false)->orderBy('name')->get() : collect(),
            'canBook'    => in_array($request->user()->role, ['admin', 'staff'], true),
        ]);
    }

    /** @return array<string, mixed> */
    private function overview(Hotel $hotel): array
    {
        $statuses = $this->status->forHotel($hotel, today());
        $rooms = $hotel->rooms()->where('is_active', true)->orderBy('floor')->orderBy('room_number')->get();

        $count = ['occupied' => 0, 'arriving' => 0, 'departing' => 0, 'turnover' => 0, 'double' => 0];
        foreach ($rooms as $room) {
            $state = $statuses[$room->id]['status'] ?? null;
            if ($state) {
                $count[$state]++;
            }
        }

        // Tonight: rooms that stay, plus rooms with a new guest arriving. Rooms only departing are vacant tonight.
        $occupied = $count['occupied'] + $count['arriving'] + $count['turnover'] + $count['double'];

        return [
            'floors'     => $rooms->groupBy('floor'),
            'statuses'   => $statuses,
            'total'      => $rooms->count(),
            'beds'       => (int) $rooms->sum('beds'),
            'occupied'   => $occupied,
            'vacant'     => $rooms->count() - $occupied,
            'arrivals'   => $count['arriving'] + $count['turnover'],
            'departures' => $count['departing'] + $count['turnover'],
            'double'     => $count['double'],
            'bookings'   => Booking::where('hotel_id', $hotel->id)
                ->with('company:id,name')
                ->withCount('rooms')
                ->orderByDesc('check_in')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ];
    }
}
