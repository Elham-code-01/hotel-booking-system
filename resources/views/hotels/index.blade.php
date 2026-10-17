<x-app-layout>
    <x-slot name="header">Hotels</x-slot>

    <x-page-header title="Hotels" sub="Choose a location to see its hotel, rooms and bookings.">
        @if ($isAdmin)
            <a class="btn btn-primary" href="{{ route('hotels.create') }}">＋ Add hotel</a>
        @endif
    </x-page-header>

    @if ($locations->isEmpty())
        <x-panel title="Hotels">
            <x-empty-state title="No hotels yet" icon="H">
                @if ($isAdmin)
                    Use the “Add hotel” button above to add the first hotel.
                @else
                    An administrator has not added a hotel yet.
                @endif
            </x-empty-state>
        </x-panel>
    @else
        <x-panel title="Location">
            <div class="chip-row">
                @foreach ($locations as $city => $group)
                    <a class="chip-link {{ $city === $location ? 'active' : '' }}" href="{{ route('hotels', ['location' => $city]) }}">
                        {{ $city }} <small>{{ $group->count() }} {{ $group->count() === 1 ? 'hotel' : 'hotels' }}</small>
                    </a>
                @endforeach
            </div>

            @if ($inLocation->count() > 1)
                <div class="chip-row" style="margin-top:12px">
                    @foreach ($inLocation as $option)
                        <a class="chip-link {{ $hotel && $hotel->id === $option->id ? 'active' : '' }}" href="{{ route('hotels', ['location' => $location, 'hotel' => $option->id]) }}">{{ $option->name }}</a>
                    @endforeach
                </div>
            @endif
        </x-panel>

        @if ($hotel)
            <x-panel :title="$hotel->name">
                <div class="form-grid">
                    <div><div class="eyebrow">Location</div>{{ $hotel->city }}</div>
                    <div><div class="eyebrow">Address</div>{{ $hotel->address ?: 'Not set' }}</div>
                    <div><div class="eyebrow">Hotel representative</div>{{ $hotel->representative_name ?: 'Not assigned' }}</div>
                    <div><div class="eyebrow">Contact</div>{{ $hotel->contact ?: 'Not set' }}</div>
                </div>
                <div class="toolbar" style="margin-top:14px">
                    <a class="btn btn-sm btn-primary" href="{{ route('bookings.index', $hotel) }}">All bookings</a>
                    @if ($canBook)
                        <a class="btn btn-sm" href="{{ route('bookings.create', ['hotel_id' => $hotel->id]) }}">＋ New booking</a>
                    @endif
                    @if ($isAdmin)
                        <a class="btn btn-sm" href="{{ route('hotels.manage', $hotel) }}">Edit hotel and rooms</a>
                    @endif
                </div>
            </x-panel>

            <div class="grid stats-grid">
                <x-stat-card label="Rooms" :value="$overview['total']" :note="$overview['beds'].' beds'" mark="R" />
                <x-stat-card label="Occupied tonight" :value="$overview['occupied']" note="Staying or arriving today" mark="●" />
                <x-stat-card label="Vacant tonight" :value="$overview['vacant']" note="Free for booking" mark="○" />
                <x-stat-card label="Arrivals today" :value="$overview['arrivals']" note="Rooms checking in" mark="→" />
                <x-stat-card label="Departures today" :value="$overview['departures']" note="Rooms checking out" mark="←" />
            </div>

            @if ($overview['double'] > 0)
                <div class="notice error" style="margin-bottom:16px">{{ $overview['double'] }} room(s) are booked twice today. Open the bookings and fix the dates or rooms.</div>
            @endif

            <x-panel title="Rooms today">
                <div class="legend">
                    <span><span class="room-chip" style="min-width:0;padding:2px 8px">Vacant</span></span>
                    <span><span class="room-chip occupied" style="min-width:0;padding:2px 8px">Occupied</span></span>
                    <span><span class="room-chip arriving" style="min-width:0;padding:2px 8px">Arriving</span></span>
                    <span><span class="room-chip departing" style="min-width:0;padding:2px 8px">Departing</span></span>
                    <span><span class="room-chip turnover" style="min-width:0;padding:2px 8px">Departing and arriving</span></span>
                </div>

                @forelse ($overview['floors'] as $floor => $rooms)
                    <div class="room-floor">
                        <h4><span>Floor {{ $floor }} · {{ $rooms->count() }} rooms</span></h4>
                        <div class="room-grid" style="display:flex;flex-wrap:wrap;gap:8px">
                            @foreach ($rooms as $room)
                                @php
                                    $state = $overview['statuses'][$room->id]['status'] ?? 'vacant';
                                    $company = $overview['statuses'][$room->id]['company'] ?? '';
                                @endphp
                                <span class="room-chip {{ $state }}" title="{{ $room->room_number }} · {{ $room->beds }} beds · {{ ucfirst($state) }}{{ $company ? ' · '.$company : '' }}">{{ $room->room_number }}</span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <x-empty-state title="No rooms yet" icon="▤">
                        @if ($isAdmin) Add rooms with <a href="{{ route('hotels.manage', $hotel) }}"><strong>Edit hotel and rooms</strong></a>. @else An administrator has not added rooms yet. @endif
                    </x-empty-state>
                @endforelse
            </x-panel>

            <x-panel title="Latest bookings">
                @if ($overview['bookings']->isEmpty())
                    <x-empty-state title="No bookings yet" icon="▤">Bookings of this hotel appear here.</x-empty-state>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Company</th><th>Rooms</th><th>Check-in</th><th>Check-out</th><th>Total USD</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($overview['bookings'] as $booking)
                                    <tr>
                                        <td>{{ $booking->id }}</td>
                                        <td><strong>{{ $booking->company->name }}</strong></td>
                                        <td>{{ $booking->rooms_count }}</td>
                                        <td>{{ $booking->check_in->format('Y-m-d') }}</td>
                                        <td>{{ $booking->check_out->format('Y-m-d') }}</td>
                                        <td><strong><x-money :value="$booking->totalUsd()" /></strong></td>
                                        <td><x-status-badge :status="$booking->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-panel>
        @endif
    @endif

    @if ($isAdmin && $inactive->isNotEmpty())
        <x-panel title="Inactive hotels">
            <div class="chip-row">
                @foreach ($inactive as $closed)
                    <a class="chip-link" href="{{ route('hotels.manage', $closed) }}">{{ $closed->name }} <small>{{ $closed->city }} · inactive</small></a>
                @endforeach
            </div>
            <p class="muted" style="margin-top:10px">Open one to make it active again.</p>
        </x-panel>
    @endif
</x-app-layout>
