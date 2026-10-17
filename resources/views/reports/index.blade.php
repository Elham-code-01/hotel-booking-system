<x-app-layout>
    <x-slot name="header">Reports</x-slot>

    <x-page-header title="Reports" sub="Occupancy, bookings and company balances. Every report can be downloaded for Excel.">
        <a class="btn" href="{{ route('reports.csv', $query) }}">Download CSV</a>
        <button class="btn" type="button" data-action="print">Print</button>
    </x-page-header>

    <div class="report-tabs">
        @foreach (['daily' => 'Daily report', 'occupancy' => 'Occupancy by day', 'bookings' => 'Bookings', 'balances' => 'Company balances'] as $key => $label)
            <a class="btn btn-sm {{ $p['report'] === $key ? 'btn-primary' : '' }}" href="{{ route('reports', array_merge($query, ['report' => $key])) }}">{{ $label }}</a>
        @endforeach
    </div>

    @error('to')<div class="notice error" style="margin-bottom:16px">{{ $message }}</div>@enderror

    @if ($p['report'] === 'daily')
        <x-panel title="Choose the day">
            <form method="GET" class="toolbar">
                <input type="hidden" name="report" value="daily">
                <input type="date" name="day" value="{{ $p['day'] }}" aria-label="Day">
                <button class="btn btn-sm btn-primary" type="submit">Show</button>
            </form>
        </x-panel>

        <x-panel :title="'Daily report · '.$daily['day']">
            @if (count($daily['hotels']) === 0)
                <x-empty-state title="No hotels yet" icon="H">Add a hotel first.</x-empty-state>
            @else
                <div class="table-wrap"><table>
                    <thead><tr><th>Hotel</th><th>Location</th><th>Rooms</th><th>Occupied</th><th>Vacant</th><th>Occupancy</th><th>Arrivals</th><th>Departures</th></tr></thead>
                    <tbody>
                        @foreach ($daily['hotels'] as $row)
                            <tr>
                                <td><strong>{{ $row['hotel']->name }}</strong></td><td>{{ $row['hotel']->city }}</td>
                                <td>{{ $row['total'] }}</td><td>{{ $row['occupied'] }}</td><td>{{ $row['vacant'] }}</td>
                                <td><strong>{{ number_format($row['percent'], 1) }}%</strong></td><td>{{ $row['arrivals'] }}</td><td>{{ $row['departures'] }}</td>
                            </tr>
                        @endforeach
                        @php($t = $daily['totals'])
                        <tr>
                            <td><strong>All hotels</strong></td><td></td>
                            <td><strong>{{ $t['total'] }}</strong></td><td><strong>{{ $t['occupied'] }}</strong></td><td><strong>{{ $t['vacant'] }}</strong></td>
                            <td><strong>{{ number_format($t['percent'], 1) }}%</strong></td><td><strong>{{ $t['arrivals'] }}</strong></td><td><strong>{{ $t['departures'] }}</strong></td>
                        </tr>
                    </tbody>
                </table></div>
                <p class="muted" style="margin-top:10px">Occupied = rooms booked for the night of this day (check-in day counts, check-out day does not).</p>
            @endif
        </x-panel>

        <div class="grid cols-2">
            <x-panel title="Arriving">
                @php($any = false)
                @foreach ($daily['hotels'] as $row)
                    @foreach ($row['arriving'] as $booking)
                        @php($any = true)
                        <p style="margin:0 0 6px"><strong>{{ $booking->company->name }}</strong> · {{ $row['hotel']->name }} · {{ $booking->rooms_count }} rooms</p>
                    @endforeach
                @endforeach
                @unless ($any)<p class="muted">Nobody arrives on this day.</p>@endunless
            </x-panel>
            <x-panel title="Departing">
                @php($any = false)
                @foreach ($daily['hotels'] as $row)
                    @foreach ($row['departing'] as $booking)
                        @php($any = true)
                        <p style="margin:0 0 6px"><strong>{{ $booking->company->name }}</strong> · {{ $row['hotel']->name }} · {{ $booking->rooms_count }} rooms</p>
                    @endforeach
                @endforeach
                @unless ($any)<p class="muted">Nobody leaves on this day.</p>@endunless
            </x-panel>
        </div>
    @endif

    @if ($p['report'] === 'occupancy' || $p['report'] === 'bookings')
        <x-panel title="Choose the period">
            <form method="GET" class="toolbar">
                <input type="hidden" name="report" value="{{ $p['report'] }}">
                <input type="date" name="from" value="{{ $p['from'] }}" aria-label="From">
                <input type="date" name="to" value="{{ $p['to'] }}" aria-label="To">
                @if ($p['report'] === 'bookings')
                    <select name="hotel_id" aria-label="Hotel">
                        <option value="">All hotels</option>
                        @foreach ($hotels as $hotel)<option value="{{ $hotel->id }}" @selected($p['hotel_id'] === $hotel->id)>{{ $hotel->name }}</option>@endforeach
                    </select>
                    <select name="status" aria-label="Status">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Booking::STATUSES as $status)<option value="{{ $status }}" @selected($p['status'] === $status)>{{ ucfirst($status) }}</option>@endforeach
                    </select>
                @endif
                <button class="btn btn-sm btn-primary" type="submit">Show</button>
            </form>
            <p class="muted" style="margin-top:10px">A report covers at most 93 days.</p>
        </x-panel>
    @endif

    @if ($p['report'] === 'occupancy')
        <x-panel :title="'Occupancy · '.$p['from'].' to '.$p['to']">
            @if ($occupancy['hotels']->isEmpty())
                <x-empty-state title="No hotels yet" icon="H">Add a hotel first.</x-empty-state>
            @else
                <div class="table-wrap"><table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            @foreach ($occupancy['hotels'] as $hotel)<th>{{ $hotel->name }}</th>@endforeach
                            <th>All hotels</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($occupancy['rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                @foreach ($occupancy['hotels'] as $hotel)
                                    <td>{{ $row['cells'][$hotel->id]['occupied'] }} · <strong>{{ number_format($row['cells'][$hotel->id]['percent'], 1) }}%</strong></td>
                                @endforeach
                                <td>{{ $row['occupied'] }} · <strong>{{ number_format($row['percent'], 1) }}%</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </x-panel>
    @endif

    @if ($p['report'] === 'bookings')
        <x-panel :title="'Bookings · '.$bookings->count()">
            @if ($bookings->isEmpty())
                <x-empty-state title="No bookings" icon="▤">No booking touches this period.</x-empty-state>
            @else
                <div class="table-wrap"><table>
                    <thead><tr><th>#</th><th>Hotel</th><th>Company</th><th>Rooms</th><th>Check-in</th><th>Check-out</th><th>Nights</th><th>SAR / night</th><th>Total USD</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td><td>{{ $booking->hotel->name }}</td><td><strong>{{ $booking->company->name }}</strong></td>
                                <td>{{ $booking->rooms_count }}</td><td>{{ $booking->check_in->format('Y-m-d') }}</td><td>{{ $booking->check_out->format('Y-m-d') }}</td>
                                <td>{{ $booking->nights() }}</td><td>{{ number_format((float) $booking->price_sar, 2) }}</td>
                                <td><strong><x-money :value="$booking->totalUsd()" /></strong></td><td><x-status-badge :status="$booking->status" /></td>
                            </tr>
                        @endforeach
                        <tr><td colspan="8"><strong>Confirmed total</strong></td><td colspan="2"><strong><x-money :value="$bookings->where('status', 'confirmed')->sum(fn ($b) => $b->totalUsd())" /></strong></td></tr>
                    </tbody>
                </table></div>
            @endif
        </x-panel>
    @endif

    @if ($p['report'] === 'balances')
        <x-panel title="Company balances">
            @if (count($balances) === 0)
                <x-empty-state title="No companies yet" icon="▤">Add companies first.</x-empty-state>
            @else
                <div class="table-wrap"><table>
                    <thead><tr><th>Company</th><th>Total charged</th><th>Paid</th><th>Balance due</th></tr></thead>
                    <tbody>
                        @foreach ($balances as $row)
                            <tr>
                                <td><a href="{{ route('company-account', ['company_id' => $row['id']]) }}"><strong>{{ $row['name'] }}</strong></a></td>
                                <td><x-money :value="$row['charged']" /></td><td><x-money :value="$row['paid']" /></td>
                                <td class="{{ $row['balance'] > 0.005 ? 'balance-due' : 'balance-credit' }}"><x-money :value="$row['balance']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
                <p class="muted" style="margin-top:10px">Balance due = total charged − paid. A number in brackets means the company has paid more than it has used.</p>
            @endif
        </x-panel>
    @endif
</x-app-layout>
