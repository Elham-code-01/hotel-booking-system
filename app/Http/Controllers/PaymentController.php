<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\Audit;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->integer('company_id') ?: null;

        $payments = Payment::query()
            ->with('company:id,name')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('payments.index', [
            'payments'  => $payments,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'companyId' => $companyId,
            'canDelete' => $request->user()->role === 'admin',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'paid_on'    => ['required', 'date', 'before_or_equal:today'],
            'amount_usd' => ['required', 'numeric', 'between:-1000000,1000000', 'not_in:0'],
            'method'     => ['nullable', 'string', 'max:30'],
            'reference'  => ['nullable', 'string', 'max:50'],
            'note'       => ['nullable', 'string', 'max:255'],
        ]);

        $payment = new Payment($data);
        $payment->forceFill(['created_by' => $request->user()->id])->save();

        Audit::record('payment.created', ['by' => $request->user()->email, 'payment' => $payment->id, 'amount' => $payment->amount_usd]);

        return back()->with('success', 'Payment saved.');
    }

    /** Admin only. A wrong entry is deleted and entered again; a refund is a negative amount. */
    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        Audit::record('payment.deleted', [
            'by' => $request->user()->email, 'payment' => $payment->id,
            'company' => $payment->company_id, 'amount' => $payment->amount_usd, 'date' => $payment->paid_on->toDateString(),
        ]);

        $payment->delete();

        return back()->with('success', 'Payment deleted.');
    }
}
