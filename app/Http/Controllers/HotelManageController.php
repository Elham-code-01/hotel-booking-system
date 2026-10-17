<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** Add a hotel and manage its details and rooms (administrator only; see config/navigation.php). */
class HotelManageController extends Controller
{
    public function create(): View
    {
        return view('hotels.create', [
            'cities' => Hotel::query()->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100', 'unique:hotels,name'],
            'city'                => ['required', 'string', 'max:60'],
            'address'             => ['nullable', 'string', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:100'],
            'contact'             => ['nullable', 'string', 'max:100'],
        ]);

        $hotel = Hotel::create($data + ['is_active' => true]);

        Log::info('hotel.created', ['by' => $request->user()->email, 'hotel' => $hotel->id]);

        return redirect()->route('hotels.manage', $hotel)
            ->with('success', $hotel->name.' was added. Now add its rooms below.');
    }

    public function show(Hotel $hotel): View
    {
        return view('hotels.manage', [
            'hotel'  => $hotel,
            'floors' => $hotel->rooms()->orderBy('floor')->orderBy('room_number')->get()->groupBy('floor'),
            'cities' => Hotel::query()->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function update(Request $request, Hotel $hotel): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100', Rule::unique('hotels', 'name')->ignore($hotel->id)],
            'city'                => ['required', 'string', 'max:60'],
            'address'             => ['nullable', 'string', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:100'],
            'contact'             => ['nullable', 'string', 'max:100'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $hasCurrentBookings = $hotel->bookings()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->where('check_out', '>=', today())
            ->exists();

        if (! $data['is_active'] && $hasCurrentBookings) {
            return back()->withErrors(['is_active' => 'This hotel has current or upcoming confirmed bookings, so it cannot be made inactive.'])->withInput();
        }

        $hotel->update($data);

        Log::info('hotel.updated', ['by' => $request->user()->email, 'hotel' => $hotel->id]);

        return back()->with('success', 'Hotel details saved.');
    }
}
