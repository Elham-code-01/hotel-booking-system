<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The single place where occupancy and booking reports are calculated.
 * "Occupied" for a day D means: a confirmed booking with check-in <= D < check-out (the night of D).
 */
class ReportService
{
    /**
     * One day, every active hotel.
     *
     * @return array{day: string, hotels: array<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public function daily(string $day): array
    {
        $hotels = $this->hotels();
        $roomTotals = $this->roomTotals();

        $bookings = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->where('check_in', '<=', $day)
            ->where('check_out', '>=', $day)
            ->with('company:id,name')
            ->withCount('rooms')
            ->get()
            ->groupBy('hotel_id');

        $rows = [];
        $sum = ['total' => 0, 'occupied' => 0, 'arrivals' => 0, 'departures' => 0];

        foreach ($hotels as $hotel) {
            $group = $bookings->get($hotel->id, collect());

            $staying = $group->filter(fn ($b) => $b->check_in->toDateString() <= $day && $b->check_out->toDateString() > $day);
            $arriving = $group->filter(fn ($b) => $b->check_in->toDateString() === $day)->values();
            $departing = $group->filter(fn ($b) => $b->check_out->toDateString() === $day)->values();

            $total = (int) ($roomTotals[$hotel->id] ?? 0);
            $occupied = (int) $staying->sum('rooms_count');

            $rows[] = [
                'hotel'      => $hotel,
                'total'      => $total,
                'occupied'   => $occupied,
                'vacant'     => $total - $occupied,
                'percent'    => $this->percent($occupied, $total),
                'arrivals'   => (int) $arriving->sum('rooms_count'),
                'departures' => (int) $departing->sum('rooms_count'),
                'arriving'   => $arriving,
                'departing'  => $departing,
            ];

            $sum['total'] += $total;
            $sum['occupied'] += $occupied;
            $sum['arrivals'] += (int) $arriving->sum('rooms_count');
            $sum['departures'] += (int) $departing->sum('rooms_count');
        }

        $sum['vacant'] = $sum['total'] - $sum['occupied'];
        $sum['percent'] = $this->percent($sum['occupied'], $sum['total']);

        return ['day' => $day, 'hotels' => $rows, 'totals' => $sum];
    }

    /**
     * Occupied rooms per day and hotel for a period.
     *
     * @return array{hotels: Collection, rows: array<int, array<string, mixed>>}
     */
    public function occupancy(string $from, string $to): array
    {
        $hotels = $this->hotels();
        $roomTotals = $this->roomTotals();
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        $matrix = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            foreach ($hotels as $hotel) {
                $matrix[$day->toDateString()][$hotel->id] = 0;
            }
        }

        $bookings = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->where('check_in', '<=', $to)
            ->where('check_out', '>', $from)
            ->withCount('rooms')
            ->get();

        foreach ($bookings as $booking) {
            if (! $hotels->contains('id', $booking->hotel_id)) {
                continue;
            }

            $first = $booking->check_in->copy()->startOfDay()->max($start);
            $last = $booking->check_out->copy()->startOfDay()->subDay()->min($end);

            for ($day = $first->copy(); $day->lte($last); $day->addDay()) {
                $matrix[$day->toDateString()][$booking->hotel_id] += $booking->rooms_count;
            }
        }

        $grandTotal = (int) array_sum(array_map(fn ($id) => $roomTotals[$id] ?? 0, $hotels->pluck('id')->all()));

        $rows = [];
        foreach ($matrix as $date => $perHotel) {
            $cells = [];
            foreach ($hotels as $hotel) {
                $cells[$hotel->id] = [
                    'occupied' => $perHotel[$hotel->id],
                    'percent'  => $this->percent($perHotel[$hotel->id], (int) ($roomTotals[$hotel->id] ?? 0)),
                ];
            }
            $occupied = array_sum($perHotel);
            $rows[] = ['date' => $date, 'cells' => $cells, 'occupied' => $occupied, 'percent' => $this->percent($occupied, $grandTotal)];
        }

        return ['hotels' => $hotels, 'rows' => $rows];
    }

    /** Bookings that touch the period. */
    public function bookings(string $from, string $to, ?int $hotelId, ?string $status): Collection
    {
        return Booking::query()
            ->where('check_in', '<=', $to)
            ->where('check_out', '>', $from)
            ->when($hotelId, fn ($q) => $q->where('hotel_id', $hotelId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['company:id,name', 'hotel:id,name'])
            ->withCount('rooms')
            ->orderBy('check_in')
            ->orderBy('id')
            ->get();
    }

    private function hotels(): Collection
    {
        return Hotel::where('is_active', true)->orderBy('city')->orderBy('name')->get();
    }

    private function roomTotals(): Collection
    {
        return Room::where('is_active', true)->selectRaw('hotel_id, COUNT(*) as total')->groupBy('hotel_id')->pluck('total', 'hotel_id');
    }

    private function percent(int|float $part, int|float $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}
