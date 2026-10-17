@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bookings.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/representative.js') }}" defer></script>
@endpush

<x-app-layout>
    <x-slot name="header">Hotel representative</x-slot>

    <x-page-header title="Hotel representative" sub="Mark rooms as occupied when guests arrive, and as checked out when they leave.">
        @if ($hotel)
            <form method="GET" class="toolbar" style="margin:0">
                <input type="hidden" name="hotel" value="{{ $hotel->id }}">
                <input type="date" name="date" value="{{ $day }}" aria-label="Day">
                <button class="btn btn-sm" type="submit">Show day</button>
            </form>
        @endif
    </x-page-header>

    @if (! $assigned)
        <x-panel title="Hotel">
            <x-empty-state title="No hotel assigned" icon="H">Ask an administrator to assign a hotel to your account.</x-empty-state>
        </x-panel>
    @elseif (! $hotel)
        <x-panel title="Hotel">
            <x-empty-state title="No active hotel" icon="H">There is no active hotel to show.</x-empty-state>
        </x-panel>
    @else
        @if ($hotels->count() > 1)
            <x-panel title="Hotel">
                <div class="chip-row">
                    @foreach ($hotels as $option)
                        <a class="chip-link {{ $option->id === $hotel->id ? 'active' : '' }}" href="{{ route('representative', ['hotel' => $option->id, 'date' => $day]) }}">
                            {{ $option->name }} <small>{{ $option->city }}</small>
                        </a>
                    @endforeach
                </div>
            </x-panel>
        @endif

        <div class="grid stats-grid">
            <x-stat-card label="Expected arrivals" :value="$stats['expected']" note="Rooms not yet marked occupied" mark="→" />
            <x-stat-card label="Guests in house" :value="$stats['in_house']" note="Rooms marked occupied" mark="●" />
            <x-stat-card label="Departing today" :value="$stats['departing']" note="Occupied, leaving today" mark="←" />
            <x-stat-card label="Overdue check-outs" :value="$stats['overdue']" note="Past their check-out day" mark="!" />
        </div>

        @error('rooms')<div class="notice error" style="margin-bottom:16px">{{ $message }}</div>@enderror

        @forelse ($bookings as $booking)
            @php
                $counts = $booking->rooms->countBy(fn ($room) => $room->pivot->operational_status);
                $overdue = $booking->check_out->toDateString() < $day && ($counts['occupied'] ?? 0) > 0;
            @endphp
            <x-panel :title="$booking->company->name.' · '.$booking->check_in->format('M j').' → '.$booking->check_out->format('M j')">
                <p class="muted" style="margin:0 0 10px">
                    {{ $booking->rooms->count() }} rooms ·
                    {{ $counts['booked'] ?? 0 }} booked · {{ $counts['occupied'] ?? 0 }} occupied · {{ $counts['checked_out'] ?? 0 }} checked out
                    @if ($overdue) · <strong style="color:var(--danger)">check-out is overdue</strong> @endif
                </p>

                <form method="POST" action="{{ route('representative.update') }}" class="rep-form">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                    <div class="room-grid">
                        @foreach ($booking->rooms as $room)
                            @php($state = $room->pivot->operational_status)
                            <label class="room-pick {{ $state }}" title="{{ $room->room_number }} · {{ str_replace('_', ' ', $state) }}">
                                <input type="checkbox" name="rooms[]" value="{{ $room->id }}">
                                <span>{{ $room->room_number }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="rep-note">Plain = booked · green outline = occupied · crossed out = checked out. Tick the rooms, then press a button.</div>

                    <div class="toolbar">
                        <button type="button" class="btn btn-sm" data-select-all>Select all</button>
                        <button type="submit" name="action" value="occupied" class="btn btn-sm btn-primary">Mark occupied</button>
                        <button type="submit" name="action" value="checked_out" class="btn btn-sm">Mark checked out</button>
                        <button type="submit" name="action" value="reset" class="btn btn-sm btn-danger" onclick="return confirm('Set the selected rooms back to booked?')">Undo</button>
                    </div>
                </form>
            </x-panel>
        @empty
            <x-panel title="Bookings">
                <x-empty-state title="Nothing to do on this day" icon="✓">No confirmed booking is arriving, staying or leaving on {{ $day }}.</x-empty-state>
            </x-panel>
        @endforelse
    @endif
</x-app-layout>
