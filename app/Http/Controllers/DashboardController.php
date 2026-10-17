<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AccountService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports, AccountService $accounts): View|RedirectResponse
    {
        if ($request->user()->role === 'representative') {
            return redirect()->route('representative');
        }

        $requested = (string) $request->query('date');
        $day = ($requested !== '' && strtotime($requested)) ? Carbon::parse($requested)->toDateString() : today()->toDateString();

        $totals = $accounts->totalsByCompany(Company::get(['id']));
        $money = [
            'charged' => round(array_sum(array_column($totals, 'charged')), 2),
            'paid'    => round(array_sum(array_column($totals, 'paid')), 2),
            'balance' => round(array_sum(array_column($totals, 'balance')), 2),
        ];

        $daily = $reports->daily($day);

        $arrivals = [];
        $departures = [];
        foreach ($daily['hotels'] as $row) {
            foreach ($row['arriving'] as $booking) {
                $arrivals[] = ['hotel' => $row['hotel']->name, 'company' => $booking->company->name, 'rooms' => $booking->rooms_count];
            }
            foreach ($row['departing'] as $booking) {
                $departures[] = ['hotel' => $row['hotel']->name, 'company' => $booking->company->name, 'rooms' => $booking->rooms_count];
            }
        }

        return view('dashboard', [
            'day'        => $day,
            'daily'      => $daily,
            'money'      => $money,
            'arrivals'   => $arrivals,
            'departures' => $departures,
        ]);
    }
}
