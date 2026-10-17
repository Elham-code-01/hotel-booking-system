<x-app-layout>
    <x-slot name="header">Payments</x-slot>

    <x-page-header title="Payments" sub="Every payment received, with its date. A refund is a negative amount." />

    <x-panel title="Record a payment">
        <form method="POST" action="{{ route('payments.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="company_id">Company</label>
                <select id="company_id" name="company_id" required>
                    <option value="">Choose a company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id') === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                @error('company_id')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="paid_on">Payment date</label>
                <input id="paid_on" name="paid_on" type="date" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                @error('paid_on')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="amount_usd">Amount (USD)</label>
                <input id="amount_usd" name="amount_usd" type="number" step="0.01" value="{{ old('amount_usd') }}" placeholder="3000" required>
                @error('amount_usd')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="method">Method (optional)</label>
                <input id="method" name="method" list="methods" maxlength="30" value="{{ old('method') }}">
                <datalist id="methods"><option value="Cash"><option value="Bank transfer"><option value="Hawala"></datalist>
            </div>
            <div class="field">
                <label for="reference">Receipt number (optional)</label>
                <input id="reference" name="reference" maxlength="50" value="{{ old('reference') }}">
            </div>
            <div class="field">
                <label for="note">Note (optional)</label>
                <input id="note" name="note" maxlength="255" value="{{ old('note') }}">
            </div>
            <div><button class="btn btn-primary" type="submit">Save payment</button></div>
        </form>
    </x-panel>

    <x-panel title="Payments received">
        <form method="GET" class="toolbar" style="margin-bottom:14px">
            <select name="company_id" aria-label="Company">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected($companyId === $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm" type="submit">Filter</button>
        </form>

        @if ($payments->isEmpty())
            <x-empty-state title="No payments yet" icon="$">Recorded payments appear here.</x-empty-state>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Company</th><th>Amount</th><th>Method</th><th>Receipt</th><th>Note</th>@if ($canDelete)<th></th>@endif</tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_on->format('Y-m-d') }}</td>
                                <td><strong>{{ $payment->company->name }}</strong></td>
                                <td><strong><x-money :value="$payment->amount_usd" /></strong></td>
                                <td>{{ $payment->method }}</td>
                                <td>{{ $payment->reference }}</td>
                                <td>{{ $payment->note }}</td>
                                @if ($canDelete)
                                    <td>
                                        <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete this payment?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px">{{ $payments->links() }}</div>
        @endif
    </x-panel>
</x-app-layout>
