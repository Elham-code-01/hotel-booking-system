<x-app-layout>
    <x-slot name="header">Company account</x-slot>

    <x-page-header title="Company account" sub="The full statement of one company.">
        @if ($statement)
            <button class="btn" type="button" data-action="print">Print</button>
        @endif
    </x-page-header>

    <x-panel title="Choose a company">
        <form method="GET" class="toolbar">
            <select name="company_id" aria-label="Company" required>
                <option value="">Choose a company</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected($selected && $selected->id === $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Show account</button>
        </form>
    </x-panel>

    @if (! $statement)
        <x-panel title="Statement">
            <x-empty-state title="No company selected" icon="▧">Choose a company above to see its bookings, payments and balance.</x-empty-state>
        </x-panel>
    @else
        <div class="grid stats-grid">
            <x-stat-card label="Total charged" :value="'$'.number_format($statement['charged'], 2)" note="Confirmed bookings and other charges" mark="＋" />
            <x-stat-card label="Total paid" :value="'$'.number_format($statement['paid'], 2)" :note="$statement['payments']->count().' payments'" mark="$" />
            <x-stat-card label="Balance due" :value="$statement['balance'] < 0 ? '($'.number_format(abs($statement['balance']), 2).')' : '$'.number_format($statement['balance'], 2)" :note="$statement['balance'] > 0.005 ? 'Company still owes this amount' : 'Nothing owed'" mark="=" />
        </div>

        <x-panel :title="$selected->name.' · payments received'">
            @if ($statement['payments']->isEmpty())
                <x-empty-state title="No payments yet" icon="$">No payment has been recorded for this company.</x-empty-state>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Receipt</th><th>Note</th></tr></thead>
                        <tbody>
                            @foreach ($statement['payments'] as $payment)
                                <tr>
                                    <td>{{ $payment->paid_on->format('Y-m-d') }}</td>
                                    <td><strong><x-money :value="$payment->amount_usd" /></strong></td>
                                    <td>{{ $payment->method }}</td>
                                    <td>{{ $payment->reference }}</td>
                                    <td>{{ $payment->note }}</td>
                                </tr>
                            @endforeach
                            <tr><td><strong>Total</strong></td><td><strong><x-money :value="$statement['paid']" /></strong></td><td colspan="3"></td></tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        <x-panel title="Bookings">
            @if ($statement['bookings']->isEmpty())
                <x-empty-state title="No confirmed bookings" icon="▤">Only confirmed bookings are charged.</x-empty-state>
            @else
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Hotel</th><th>Rooms</th><th>Check-in</th><th>Check-out</th><th>Nights</th><th>SAR / night</th><th>Rate</th><th>Total USD</th></tr></thead>
                        <tbody>
                            @foreach ($statement['bookings'] as $booking)
                                <tr>
                                    <td>{{ $booking->hotel->name }}</td>
                                    <td title="{{ $booking->rooms->pluck('room_number')->sort()->join(', ') }}">{{ $booking->rooms_count }}</td>
                                    <td>{{ $booking->check_in->format('Y-m-d') }}</td>
                                    <td>{{ $booking->check_out->format('Y-m-d') }}</td>
                                    <td>{{ $booking->nights() }}</td>
                                    <td>{{ number_format((float) $booking->price_sar, 2) }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $booking->exchange_rate, 4), '0'), '.') }}</td>
                                    <td><strong><x-money :value="$booking->totalUsd()" /></strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>

        @if ($statement['charges']->isNotEmpty())
            <x-panel title="Other charges">
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Hotel</th><th>Description</th><th>Qty</th><th>Nights</th><th>SAR</th><th>Total USD</th></tr></thead>
                        <tbody>
                            @foreach ($statement['charges'] as $charge)
                                <tr>
                                    <td>{{ $charge->charged_on->format('Y-m-d') }}</td>
                                    <td>{{ $charge->hotel?->name }}</td>
                                    <td>{{ $charge->description }}</td>
                                    <td>{{ $charge->quantity }}</td>
                                    <td>{{ $charge->charge_type === 'once' ? 'once' : $charge->nights }}</td>
                                    <td>{{ number_format((float) $charge->price_sar, 2) }}</td>
                                    <td><strong><x-money :value="$charge->totalUsd()" /></strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        @endif
    @endif
</x-app-layout>
