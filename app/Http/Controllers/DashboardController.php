<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->role === 'representative') {
            return redirect()->route('representative');
        }

        $today = today();

        $roomsOn = fn (string $column) => (int) Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereDate($column, $today)
            ->withCount('rooms')
            ->get()
            ->sum('rooms_count');

        return view('dashboard', [
            'hotels'     => Hotel::where('is_active', true)->count(),
            'locations'  => Hotel::where('is_active', true)->distinct()->orderBy('city')->pluck('city')->join(', '),
            'rooms'      => Room::where('is_active', true)->count(),
            'beds'       => (int) Room::where('is_active', true)->sum('beds'),
            'arrivals'   => $roomsOn('check_in'),
            'departures' => $roomsOn('check_out'),
        ]);
    }
}
