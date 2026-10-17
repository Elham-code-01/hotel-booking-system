<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** Builds the notification list. Each alert is one small method: add or remove freely. */
class AlertService
{
    /** Set for a representative: they only ever hear about their own hotel. */
    private ?int $onlyHotel = null;

    public function __construct(private AccountService $accounts)
    {
    }

    /** @return array<int, array{level:string,title:string,text:string,url:string}> */
    public function forUser(User $user): array
    {
        $this->onlyHotel = $user->role === 'representative' ? (int) $user->hotel_id : null;

        $alerts = [
            $this->arrivalsToday(),
            $this->departuresToday(),
            $this->departuresTomorrow(),
        ];

        if (in_array($user->role, ['admin', 'staff'], true)) {
            $alerts[] = $this->oldTentative();
            $alerts[] = $this->balanceDue();
        }

        if ($user->role === 'admin') {
            $alerts[] = $this->pendingUsers();
        }

        return array_values(array_filter($alerts));
    }

    private function arrivalsToday(): ?array
    {
        $bookings = $this->confirmed()->whereDate('check_in', today())->get();

        return $this->make($bookings, 'info', 'Arrivals today');
    }

    private function departuresToday(): ?array
    {
        $bookings = $this->confirmed()->whereDate('check_out', today())->get();

        return $this->make($bookings, 'info', 'Departures today');
    }

    private function departuresTomorrow(): ?array
    {
        $bookings = $this->confirmed()->whereDate('check_out', today()->addDay())->get();

        return $this->make($bookings, 'info', 'Departures tomorrow');
    }

    private function oldTentative(): ?array
    {
        $bookings = Booking::withCount('rooms')
            ->where('status', Booking::STATUS_TENTATIVE)
            ->where('created_at', '<', now()->subDays(3))
            ->get();

        return $this->make($bookings, 'warning', 'Tentative for more than 3 days');
    }

    /** Companies that still owe money. Cached for one minute so every page stays fast. */
    private function balanceDue(): ?array
    {
        $owing = Cache::remember('alerts.balance-due', 60, function () {
            $totals = $this->accounts->totalsByCompany(Company::get(['id']));
            $rows = array_filter($totals, fn ($row) => $row['balance'] > 0.5);

            return ['count' => count($rows), 'sum' => round(array_sum(array_column($rows, 'balance')), 2)];
        });

        if ($owing['count'] === 0) {
            return null;
        }

        return [
            'level' => 'warning',
            'title' => 'Companies with a balance due',
            'text'  => $owing['count'].' '.($owing['count'] === 1 ? 'company' : 'companies').' · $'.number_format($owing['sum'], 2),
            'url'   => route('accounts'),
        ];
    }

    private function pendingUsers(): ?array
    {
        $count = User::where('status', 'pending')->count();

        if ($count === 0) {
            return null;
        }

        return [
            'level' => 'warning',
            'title' => 'Accounts waiting for approval',
            'text'  => $count.' '.($count === 1 ? 'account' : 'accounts'),
            'url'   => route('users.index'),
        ];
    }

    private function confirmed()
    {
        return Booking::withCount('rooms')
            ->where('status', Booking::STATUS_CONFIRMED)
            ->when($this->onlyHotel !== null, fn ($q) => $q->where('hotel_id', $this->onlyHotel));
    }

    private function make(Collection $bookings, string $level, string $title): ?array
    {
        if ($bookings->isEmpty()) {
            return null;
        }

        $rooms = (int) $bookings->sum('rooms_count');
        $companies = $bookings->pluck('company_id')->unique()->count();

        return [
            'level' => $level,
            'title' => $title,
            'text'  => $rooms.' '.($rooms === 1 ? 'room' : 'rooms').' · '.$companies.' '.($companies === 1 ? 'company' : 'companies'),
            'url'   => route('dashboard'),
        ];
    }
}
