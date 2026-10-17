<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Hotel;
use App\Services\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private AccountService $accounts)
    {
    }

    /** One table with every company. */
    public function overview(): View
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);
        $totals = $this->accounts->totalsByCompany($companies);

        $sum = ['other' => 0.0, 'charged' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'by_hotel' => []];
        foreach ($totals as $row) {
            foreach (['other', 'charged', 'paid', 'balance'] as $key) {
                $sum[$key] += $row[$key];
            }
            foreach ($row['by_hotel'] as $hotelId => $amount) {
                $sum['by_hotel'][$hotelId] = ($sum['by_hotel'][$hotelId] ?? 0.0) + $amount;
            }
        }

        return view('accounts.overview', [
            'companies' => $companies,
            'totals'    => $totals,
            'sum'       => $sum,
            'hotels'    => Hotel::orderBy('id')->get(['id', 'name']),
        ]);
    }

    /** The statement of one company. */
    public function company(Request $request): View
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);
        $selected = $companies->firstWhere('id', $request->integer('company_id'));

        return view('accounts.company', [
            'companies' => $companies,
            'selected'  => $selected,
            'statement' => $selected ? $this->accounts->statement($selected) : null,
        ]);
    }
}
