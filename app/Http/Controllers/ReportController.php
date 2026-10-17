<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Hotel;
use App\Services\AccountService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORTS = ['daily', 'occupancy', 'bookings', 'balances'];

    public function __construct(private ReportService $reports, private AccountService $accounts)
    {
    }

    public function index(Request $request): View
    {
        $p = $this->params($request);
        $data = [];

        switch ($p['report']) {
            case 'daily':
                $data['daily'] = $this->reports->daily($p['day']);
                break;
            case 'occupancy':
                $data['occupancy'] = $this->reports->occupancy($p['from'], $p['to']);
                break;
            case 'bookings':
                $data['bookings'] = $this->reports->bookings($p['from'], $p['to'], $p['hotel_id'], $p['status']);
                break;
            case 'balances':
                $data['balances'] = $this->balanceRows();
                break;
        }

        return view('reports.index', $data + [
            'p'      => $p,
            'hotels' => Hotel::orderBy('name')->get(['id', 'name']),
            'query'  => array_filter($p, fn ($value) => $value !== null && $value !== ''),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $p = $this->params($request);
        [$rows, $name] = $this->csvRows($p);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // lets Excel read Pashto and Arabic names correctly

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => $this->safe($value), $row), ',', '"', '');
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: array<int, array<int, mixed>>, 1: string} */
    private function csvRows(array $p): array
    {
        switch ($p['report']) {
            case 'daily':
                $daily = $this->reports->daily($p['day']);
                $rows = [['Hotel', 'Location', 'Rooms', 'Occupied', 'Vacant', 'Occupancy %', 'Arrivals', 'Departures']];
                foreach ($daily['hotels'] as $r) {
                    $rows[] = [$r['hotel']->name, $r['hotel']->city, $r['total'], $r['occupied'], $r['vacant'], $r['percent'], $r['arrivals'], $r['departures']];
                }
                $t = $daily['totals'];
                $rows[] = ['All hotels', '', $t['total'], $t['occupied'], $t['vacant'], $t['percent'], $t['arrivals'], $t['departures']];

                return [$rows, 'daily-report-'.$p['day'].'.csv'];

            case 'occupancy':
                $occupancy = $this->reports->occupancy($p['from'], $p['to']);
                $head = ['Date'];
                foreach ($occupancy['hotels'] as $hotel) {
                    $head[] = $hotel->name.' occupied';
                    $head[] = $hotel->name.' %';
                }
                $rows = [array_merge($head, ['All occupied', 'All %'])];
                foreach ($occupancy['rows'] as $r) {
                    $line = [$r['date']];
                    foreach ($occupancy['hotels'] as $hotel) {
                        $line[] = $r['cells'][$hotel->id]['occupied'];
                        $line[] = $r['cells'][$hotel->id]['percent'];
                    }
                    $rows[] = array_merge($line, [$r['occupied'], $r['percent']]);
                }

                return [$rows, 'occupancy-'.$p['from'].'-to-'.$p['to'].'.csv'];

            case 'bookings':
                $rows = [['ID', 'Hotel', 'Company', 'Rooms', 'Check-in', 'Check-out', 'Nights', 'Price SAR', 'Rate', 'Total SAR', 'Total USD', 'Status', 'Reference']];
                foreach ($this->reports->bookings($p['from'], $p['to'], $p['hotel_id'], $p['status']) as $b) {
                    $rows[] = [
                        $b->id, $b->hotel->name, $b->company->name, $b->rooms_count, $b->check_in->toDateString(), $b->check_out->toDateString(),
                        $b->nights(), (float) $b->price_sar, (float) $b->exchange_rate, $b->totalSar(), $b->totalUsd(), $b->status, $b->reference,
                    ];
                }

                return [$rows, 'bookings-'.$p['from'].'-to-'.$p['to'].'.csv'];

            default:
                $rows = [['Company', 'Total charged USD', 'Paid USD', 'Balance due USD']];
                foreach ($this->balanceRows() as $r) {
                    $rows[] = [$r['name'], $r['charged'], $r['paid'], $r['balance']];
                }

                return [$rows, 'company-balances-'.today()->toDateString().'.csv'];
        }
    }

    /** @return array<int, array{name: string, charged: float, paid: float, balance: float}> */
    private function balanceRows(): array
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);
        $totals = $this->accounts->totalsByCompany($companies);

        return $companies->map(fn ($c) => [
            'id'      => $c->id,
            'name'    => $c->name,
            'charged' => $totals[$c->id]['charged'],
            'paid'    => $totals[$c->id]['paid'],
            'balance' => $totals[$c->id]['balance'],
        ])->all();
    }

    /** @return array<string, mixed> */
    private function params(Request $request): array
    {
        $v = $request->validate([
            'report'   => ['nullable', Rule::in(self::REPORTS)],
            'day'      => ['nullable', 'date'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date', 'after_or_equal:from'],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
            'status'   => ['nullable', Rule::in(Booking::STATUSES)],
        ]);

        $from = Carbon::parse($v['from'] ?? today());
        $to = Carbon::parse($v['to'] ?? $from->copy()->addDays(13));

        if ($from->diffInDays($to) > 92) {
            throw ValidationException::withMessages(['to' => 'A report can cover at most 93 days.']);
        }

        return [
            'report'   => $v['report'] ?? 'daily',
            'day'      => Carbon::parse($v['day'] ?? today())->toDateString(),
            'from'     => $from->toDateString(),
            'to'       => $to->toDateString(),
            'hotel_id' => isset($v['hotel_id']) ? (int) $v['hotel_id'] : null,
            'status'   => $v['status'] ?? null,
        ];
    }

    /** A spreadsheet treats text that starts with = + - @ as a formula. Stop that. */
    private function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
