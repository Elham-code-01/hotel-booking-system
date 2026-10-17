<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <x-page-header :title="'Good day, '.auth()->user()->name" sub="A clear view of every hotel on the chosen day.">
        <form method="GET" class="toolbar" style="margin:0">
            <input type="date" name="date" value="{{ $day }}" aria-label="Day">
            <button class="btn btn-sm" type="submit">Show day</button>
        </form>
    </x-page-header>

    @php($t = $daily['totals'])
    <div class="grid stats-grid">
        <x-stat-card label="Rooms" :value="$t['total']" note="All active hotels" mark="R" />
        <x-stat-card label="Occupied" :value="$t['occupied']" note="Nights taken on this day" mark="●" />
        <x-stat-card label="Vacant" :value="$t['vacant']" note="Free for booking" mark="○" />
        <x-stat-card label="Occupancy" :value="number_format($t['percent'], 1).'%'" note="Occupied ÷ rooms" mark="%" />
        <x-stat-card label="Arrivals" :value="$t['arrivals']" note="Rooms checking in" mark="→" />
        <x-stat-card label="Departures" :value="$t['departures']" note="Rooms checking out" mark="←" />
    </div>

    @if (count($daily['hotels']) > 0)
        <div class="grid cols-2" style="margin-bottom:16px">
            @foreach ($daily['hotels'] as $row)
                <article class="hotel-card">
                    <div class="eyebrow">{{ $row['hotel']->city }}</div>
                    <h3>{{ $row['hotel']->name }}</h3>
                    <div class="big-percent">{{ number_format($row['percent'], 1) }}%</div>
                    <div class="meter" role="img" aria-label="Occupancy {{ number_format($row['percent'], 1) }} percent"><span style="width:{{ min(100, $row['percent']) }}%"></span></div>
                    <div class="hotel-metrics">
                        <span>{{ $row['total'] }} rooms</span>
                        <span>{{ $row['occupied'] }} occupied</span>
                        <span>{{ $row['vacant'] }} vacant</span>
                    </div>
                    <div class="muted" style="font-size:12px;margin-top:10px">{{ $row['arrivals'] }} arriving · {{ $row['departures'] }} departing</div>
                    <div style="margin-top:14px">
                        <a class="btn btn-sm" href="{{ route('hotels', ['location' => $row['hotel']->city, 'hotel' => $row['hotel']->id]) }}">Open hotel</a>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <x-panel title="Hotels">
            <x-empty-state title="No hotels yet" icon="H">Add a hotel on the Hotels page.</x-empty-state>
        </x-panel>
    @endif

    <div class="grid stats-grid">
        <x-stat-card label="Total charged" :value="'$'.number_format($money['charged'], 2)" note="Confirmed bookings and other charges" mark="＋" />
        <x-stat-card label="Total paid" :value="'$'.number_format($money['paid'], 2)" note="All payments received" mark="$" />
        <x-stat-card label="Balance due" :value="$money['balance'] < 0 ? '($'.number_format(abs($money['balance']), 2).')' : '$'.number_format($money['balance'], 2)" note="Charged − paid, all companies" mark="=" />
    </div>

    <div class="grid cols-2">
        <x-panel :title="'Arrivals · '.$day">
            @if (count($arrivals) === 0)
                <x-empty-state title="No arrivals" icon="→">No group checks in on this day.</x-empty-state>
            @else
                <div class="table-wrap"><table>
                    <thead><tr><th>Hotel</th><th>Company</th><th>Rooms</th></tr></thead>
                    <tbody>@foreach ($arrivals as $item)<tr><td>{{ $item['hotel'] }}</td><td><strong>{{ $item['company'] }}</strong></td><td>{{ $item['rooms'] }}</td></tr>@endforeach</tbody>
                </table></div>
            @endif
        </x-panel>

        <x-panel :title="'Departures · '.$day">
            @if (count($departures) === 0)
                <x-empty-state title="No departures" icon="←">No group checks out on this day.</x-empty-state>
            @else
                <div class="table-wrap"><table>
                    <thead><tr><th>Hotel</th><th>Company</th><th>Rooms</th></tr></thead>
                    <tbody>@foreach ($departures as $item)<tr><td>{{ $item['hotel'] }}</td><td><strong>{{ $item['company'] }}</strong></td><td>{{ $item['rooms'] }}</td></tr>@endforeach</tbody>
                </table></div>
            @endif
        </x-panel>
    </div>
</x-app-layout>
