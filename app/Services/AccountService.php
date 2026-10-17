<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Company;
use App\Models\OtherCharge;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * The single place where company accounts are calculated.
 * charged = confirmed bookings + other charges (in USD, using each record's own saved rate)
 * balance = charged - paid   (positive: company owes money, negative: company has credit)
 */
class AccountService
{
    /**
     * Totals for every company of the given list.
     *
     * @param  Collection<int, Company>  $companies
     * @return array<int, array{by_hotel: array<int, float>, other: float, charged: float, paid: float, balance: float}>
     */
    public function totalsByCompany(Collection $companies): array
    {
        $totals = [];
        foreach ($companies as $company) {
            $totals[$company->id] = ['by_hotel' => [], 'other' => 0.0, 'charged' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
        }

        $bookings = Booking::where('status', Booking::STATUS_CONFIRMED)->withCount('rooms')->get();
        foreach ($bookings as $booking) {
            if (! isset($totals[$booking->company_id])) {
                continue;
            }
            $current = $totals[$booking->company_id]['by_hotel'][$booking->hotel_id] ?? 0.0;
            $totals[$booking->company_id]['by_hotel'][$booking->hotel_id] = $current + $booking->totalUsd();
        }

        foreach (OtherCharge::all() as $charge) {
            if (isset($totals[$charge->company_id])) {
                $totals[$charge->company_id]['other'] += $charge->totalUsd();
            }
        }

        $paid = Payment::selectRaw('company_id, SUM(amount_usd) as total')->groupBy('company_id')->pluck('total', 'company_id');

        foreach (array_keys($totals) as $id) {
            $totals[$id]['paid'] = round((float) ($paid[$id] ?? 0), 2);
            $totals[$id]['other'] = round($totals[$id]['other'], 2);
            $totals[$id]['charged'] = round(array_sum($totals[$id]['by_hotel']) + $totals[$id]['other'], 2);
            $totals[$id]['balance'] = round($totals[$id]['charged'] - $totals[$id]['paid'], 2);
        }

        return $totals;
    }

    /**
     * Everything the statement page needs for one company.
     *
     * @return array{bookings: Collection, charges: Collection, payments: Collection, charged: float, paid: float, balance: float}
     */
    public function statement(Company $company): array
    {
        $bookings = $company->bookings()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->with(['hotel:id,name', 'rooms:id,room_number'])
            ->withCount('rooms')
            ->orderBy('check_in')
            ->get();

        $charges = $company->otherCharges()->with('hotel:id,name')->orderBy('charged_on')->get();
        $payments = $company->payments()->orderBy('paid_on')->orderBy('id')->get();

        $charged = round($bookings->sum(fn (Booking $b) => $b->totalUsd()) + $charges->sum(fn (OtherCharge $c) => $c->totalUsd()), 2);
        $paid = round((float) $payments->sum('amount_usd'), 2);

        return [
            'bookings' => $bookings,
            'charges'  => $charges,
            'payments' => $payments,
            'charged'  => $charged,
            'paid'     => $paid,
            'balance'  => round($charged - $paid, 2),
        ];
    }
}
