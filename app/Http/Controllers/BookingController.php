<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Hotel;
use App\Models\Setting;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    /** One list per hotel. The hotel comes from the address, so renaming a hotel never breaks it. */
    public function index(Request $request, Hotel $hotel): View
    {
        $filters = $request->validate([
            'status'     => ['nullable', Rule::in(Booking::STATUSES)],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $bookings = Booking::query()
            ->where('hotel_id', $hotel->id)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['company_id'] ?? null, fn ($q, $company) => $q->where('company_id', $company))
            ->with(['company:id,name', 'rooms:id,room_number'])
            ->withCount('rooms')
            ->orderByDesc('check_in')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('bookings.index', [
            'hotel'     => $hotel,
            'bookings'  => $bookings,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'filters'   => $filters,
            'canEdit'   => in_array($request->user()->role, ['admin', 'staff'], true),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formView($request, null);
    }

    public function edit(Request $request, Booking $booking): View
    {
        return $this->formView($request, $booking);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $rooms] = $this->validated($request, null);

        $booking = $this->bookings->save($data, $rooms, null, $request->user());

        Log::info('booking.created', ['by' => $request->user()->email, 'booking' => $booking->id, 'status' => $booking->status]);

        return redirect()->route('bookings.index', $booking->hotel_id)
            ->with('success', 'Booking saved: '.count($rooms).' room(s).');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        [$data, $rooms] = $this->validated($request, $booking);

        $this->bookings->save($data, $rooms, $booking, $request->user());

        Log::info('booking.updated', ['by' => $request->user()->email, 'booking' => $booking->id, 'status' => $booking->status]);

        return redirect()->route('bookings.index', $booking->hotel_id)
            ->with('success', 'Booking #'.$booking->id.' saved.');
    }

    private function formView(Request $request, ?Booking $booking): View
    {
        $hotels = Hotel::where('is_active', true)->orderBy('name')->get();
        $hotel = $booking
            ? $booking->hotel
            : ($hotels->firstWhere('id', (int) $request->query('hotel_id')) ?? $hotels->first());

        $from = $request->query('check_in', $booking?->check_in->toDateString());
        $to = $request->query('check_out', $booking?->check_out->toDateString());

        $datesReady = $hotel && $from && $to && strtotime($from) && strtotime($to) && strtotime($to) > strtotime($from);

        $rooms = collect();
        $nights = 0;

        if ($datesReady) {
            $rooms = $this->bookings->freeRooms($hotel, $from, $to, $booking?->id);

            if ($booking) {
                // Keep the rooms already in this booking visible, so saving never drops them silently.
                $rooms = $rooms->merge($booking->rooms)
                    ->unique('id')
                    ->sortBy([['floor', 'asc'], ['room_number', 'asc']])
                    ->values();
            }

            $nights = (int) Carbon::parse($from)->diffInDays(Carbon::parse($to));
        }

        $selected = collect(old('rooms', $booking ? $booking->rooms->pluck('id')->all() : []))
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('bookings.form', [
            'booking'    => $booking,
            'hotels'     => $hotels,
            'hotel'      => $hotel,
            'companies'  => Company::orderBy('name')->get(['id', 'name']),
            'from'       => $from,
            'to'         => $to,
            'datesReady' => $datesReady,
            'rooms'      => $rooms,
            'nights'     => $nights,
            'selected'   => $selected,
            'rate'       => $booking ? (float) $booking->exchange_rate : (float) Setting::get('exchange_rate', '3.75'),
            'listUrl'    => $hotel ? route('bookings.index', $hotel) : route('dashboard'),
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: array<int, int>} */
    private function validated(Request $request, ?Booking $booking): array
    {
        $data = $request->validate([
            'hotel_id'   => ['required', 'integer', Rule::exists('hotels', 'id')->where('is_active', true)],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'check_in'   => ['required', 'date'],
            'check_out'  => ['required', 'date', 'after:check_in'],
            'status'     => ['required', Rule::in(Booking::STATUSES)],
            'price_sar'  => ['required', 'numeric', 'between:0,100000'],
            'reference'  => ['nullable', 'string', 'max:40'],
            'notes'      => ['nullable', 'string', 'max:1000'],
            'rooms'      => ['required', 'array', 'min:1', 'max:200'],
            'rooms.*'    => ['integer'],
        ], [
            'rooms.required' => 'Select at least one room.',
            'rooms.min'      => 'Select at least one room.',
        ]);

        if (Carbon::parse($data['check_in'])->diffInDays(Carbon::parse($data['check_out'])) > 366) {
            throw ValidationException::withMessages(['check_out' => 'A booking can be at most 366 nights.']);
        }

        $rooms = array_map('intval', $data['rooms']);
        unset($data['rooms']);

        if ($booking) {
            $data['hotel_id'] = $booking->hotel_id; // the hotel of an existing booking never changes
        }

        return [$data, $rooms];
    }
}
