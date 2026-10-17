<x-app-layout>
    <x-slot name="header">All accounts</x-slot>

    <x-page-header title="All accounts" sub="Every company in one table. Only confirmed bookings are counted. All amounts in USD." />

    <x-panel title="Companies">
        @if ($companies->isEmpty())
            <x-empty-state title="No companies yet" icon="▤">Add companies on the Companies page first.</x-empty-state>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Company</th>
                            @foreach ($hotels as $hotel)<th>{{ $hotel->name }}</th>@endforeach
                            <th>Other charges</th><th>Total charged</th><th>Paid</th><th>Balance due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>ALL COMPANIES</strong></td>
                            @foreach ($hotels as $hotel)<td><strong><x-money :value="$sum['by_hotel'][$hotel->id] ?? 0" /></strong></td>@endforeach
                            <td><strong><x-money :value="$sum['other']" /></strong></td>
                            <td><strong><x-money :value="$sum['charged']" /></strong></td>
                            <td><strong><x-money :value="$sum['paid']" /></strong></td>
                            <td class="{{ $sum['balance'] > 0.005 ? 'balance-due' : 'balance-credit' }}"><strong><x-money :value="$sum['balance']" /></strong></td>
                        </tr>
                        @foreach ($companies as $company)
                            @php($row = $totals[$company->id])
                            <tr>
                                <td><a href="{{ route('company-account', ['company_id' => $company->id]) }}"><strong>{{ $company->name }}</strong></a></td>
                                @foreach ($hotels as $hotel)<td><x-money :value="$row['by_hotel'][$hotel->id] ?? 0" /></td>@endforeach
                                <td><x-money :value="$row['other']" /></td>
                                <td><x-money :value="$row['charged']" /></td>
                                <td><x-money :value="$row['paid']" /></td>
                                <td class="{{ $row['balance'] > 0.005 ? 'balance-due' : 'balance-credit' }}"><x-money :value="$row['balance']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="muted" style="margin-top:12px">Balance due = total charged − paid. A number in brackets means the company has paid more than it has used.</p>
        @endif
    </x-panel>
</x-app-layout>
