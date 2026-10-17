<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Hotel;
use App\Models\OtherCharge;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OtherChargeController extends Controller
{
    public function index(Request $request): View
    {
        return view('other-charges.index', [
            'charges'   => OtherCharge::with(['company:id,name', 'hotel:id,name'])->orderByDesc('charged_on')->orderByDesc('id')->paginate(25),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'hotels'    => Hotel::orderBy('id')->get(['id', 'name']),
            'canDelete' => $request->user()->role === 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id'  => ['required', 'integer', 'exists:companies,id'],
            'hotel_id'    => ['nullable', 'integer', 'exists:hotels,id'],
            'charged_on'  => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:255'],
            'charge_type' => ['required', Rule::in([OtherCharge::TYPE_NIGHTLY, OtherCharge::TYPE_ONCE])],
            'quantity'    => ['required', 'integer', 'between:1,100000'],
            'nights'      => ['nullable', 'integer', 'between:1,366'],
            'price_sar'   => ['required', 'numeric', 'between:0,100000'],
        ]);

        $data['nights'] = $data['charge_type'] === OtherCharge::TYPE_ONCE ? 1 : ($data['nights'] ?? 1);

        $charge = new OtherCharge($data);
        $charge->forceFill([
            'exchange_rate' => (float) Setting::get('exchange_rate', '3.75'),
            'created_by'    => $request->user()->id,
        ])->save();

        Log::info('charge.created', ['by' => $request->user()->email, 'charge' => $charge->id]);

        return back()->with('success', 'Charge saved.');
    }

    public function destroy(Request $request, OtherCharge $otherCharge): RedirectResponse
    {
        Log::info('charge.deleted', ['by' => $request->user()->email, 'charge' => $otherCharge->id, 'company' => $otherCharge->company_id]);

        $otherCharge->delete();

        return back()->with('success', 'Charge deleted.');
    }
}
