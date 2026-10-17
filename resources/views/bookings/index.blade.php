<x-app-layout>
    <x-slot name="header">{{ $hotel->name }} bookings</x-slot>

    <x-page-header :title="$hotel->name.' bookings'" :sub="$hotel->city.' · newest check-in first'">
        <a class="btn" href="{{ route('hotels', ['location' => $hotel->city, 'hotel' => $hotel->id]) }}">← Hotel overview</a>
        @if ($canEdit)
            <a class="btn btn-primary" href="{{ route('bookings.create', ['hotel_id' => $hotel->id]) }}">＋ New booking</a>
        @endif
    </x-page-header>

    <x-panel title="Bookings">
        <form method="GET" class="toolbar" style="margin-bottom:14px">
            <select name="status" aria-label="Status">
                <option value="">All statuses</option>
                @foreach (\App\Models\Booking::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="company_id" aria-label="Company">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((int) ($filters['company_id'] ?? 0) === $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm" type="submit">Filter</button>
        </form>

        @if ($bookings->isEmpty())
            <x-empty-state title="No bookings found" icon="▤">
                @if ($canEdit) Use “New booking” to add the first one. @else Nothing matches these filters. @endif
            </x-empty-state>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th><th>Company</th><th>Rooms</th><th>Check-in</th><th>Check-out</th>
                            <th>Nights</th><th>SAR / night</th><th>Total USD</th><th>Status</th>@if ($canEdit)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td><strong>{{ $booking->company->name }}</strong></td>
                                <td title="{{ $booking->rooms->pluck('room_number')->join(', ') }}">
                                    {{ $booking->rooms_count }} · {{ \Illuminate\Support\Str::limit($booking->rooms->pluck('room_number')->sort()->join(', '), 28) }}
                                </td>
                                <td>{{ $booking->check_in->format('Y-m-d') }}</td>
                                <td>{{ $booking->check_out->format('Y-m-d') }}</td>
                                <td>{{ $booking->nights() }}</td>
                                <td>{{ number_format((float) $booking->price_sar, 2) }}</td>
                                <td><strong>${{ number_format($booking->totalUsd(), 2) }}</strong></td>
                                <td><x-status-badge :status="$booking->status" /></td>
                                @if ($canEdit)
                                    <td><a class="btn btn-sm" href="{{ route('bookings.edit', $booking) }}">Edit</a></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px">{{ $bookings->links() }}</div>
        @endif
    </x-panel>
</x-app-layout>
