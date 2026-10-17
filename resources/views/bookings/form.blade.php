@php($editing = $booking !== null)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bookings.css') }}">
@endpush

<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Edit booking #'.$booking->id : 'New booking' }}</x-slot>

    <x-page-header :title="$editing ? 'Edit booking #'.$booking->id : 'New booking'" sub="Choose the dates first, then pick from the free rooms.">
        <a class="btn" href="{{ $listUrl }}">← Back to bookings</a>
    </x-page-header>

    <x-panel title="1 · Hotel and dates">
        <form method="GET" action="{{ $editing ? route('bookings.edit', $booking) : route('bookings.create') }}" class="form-grid">
            <div class="field">
                <label for="hotel_id">Hotel</label>
                <select id="hotel_id" name="hotel_id" @disabled($editing)>
                    @foreach ($hotels as $option)
                        <option value="{{ $option->id }}" @selected($hotel && $hotel->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="check_in">Check-in</label>
                <input id="check_in" name="check_in" type="date" value="{{ $from }}" required>
            </div>
            <div class="field">
                <label for="check_out">Check-out</label>
                <input id="check_out" name="check_out" type="date" value="{{ $to }}" required>
            </div>
            <div><button class="btn btn-primary" type="submit">Show free rooms</button></div>
        </form>
        <p class="muted" style="margin-top:10px">The check-in day counts as a night. The check-out day does not, so a room can be given to a new company on the same day.</p>
    </x-panel>

    @if (! $datesReady)
        <x-panel title="2 · Rooms and booking details">
            <x-empty-state title="Choose the dates" icon="▥">Pick a hotel, a check-in and a later check-out date, then press “Show free rooms”.</x-empty-state>
        </x-panel>
    @else
        <x-panel title="2 · Rooms and booking details">
            <form method="POST" id="booking-form" data-nights="{{ $nights }}" data-rate="{{ $rate }}"
                  action="{{ $editing ? route('bookings.update', $booking) : route('bookings.store') }}">
                @csrf
                @if ($editing) @method('PATCH') @endif
                <input type="hidden" name="hotel_id" value="{{ $hotel->id }}">
                <input type="hidden" name="check_in" value="{{ $from }}">
                <input type="hidden" name="check_out" value="{{ $to }}">

                <p><strong>{{ $hotel->name }}</strong> · {{ $from }} → {{ $to }} · {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }} · {{ $rooms->count() }} rooms available</p>

                @error('rooms')<div class="notice error" style="margin:10px 0">{{ $message }}</div>@enderror
                @error('check_out')<div class="notice error" style="margin:10px 0">{{ $message }}</div>@enderror
                @error('hotel_id')<div class="notice error" style="margin:10px 0">{{ $message }}</div>@enderror

                @if ($rooms->isEmpty())
                    <x-empty-state title="No free rooms" icon="∅">Every room of this hotel is taken for these dates.</x-empty-state>
                @else
                    <div class="picker-tools">
                        <button type="button" class="btn btn-sm" data-select-all>Select all</button>
                        <button type="button" class="btn btn-sm" data-clear>Clear</button>
                    </div>

                    @foreach ($rooms->groupBy('floor') as $floor => $group)
                        <div class="room-floor">
                            <h4>
                                <span>Floor {{ $floor }} · {{ $group->count() }} rooms</span>
                                <button type="button" class="btn btn-sm" data-floor-toggle="{{ $floor }}">Select floor</button>
                            </h4>
                            <div class="room-grid" data-floor="{{ $floor }}">
                                @foreach ($group as $room)
                                    <label class="room-pick" title="{{ $room->beds }} beds">
                                        <input type="checkbox" name="rooms[]" value="{{ $room->id }}" @checked(in_array($room->id, $selected, true))>
                                        <span>{{ $room->room_number }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="summary-bar">
                        <span>Rooms <b data-sum="rooms">0</b></span>
                        <span>Nights <b>{{ $nights }}</b></span>
                        <span>Total SAR <b data-sum="sar">0.00</b></span>
                        <span>Total USD <b data-sum="usd">0.00</b> <small class="muted">(rate {{ $rate }})</small></span>
                    </div>

                    <div class="form-grid" style="margin-top:14px">
                        <div class="field">
                            <label for="company_id">Company</label>
                            <select id="company_id" name="company_id" required>
                                <option value="">Choose a company</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((int) old('company_id', $booking?->company_id) === $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id')<span class="auth-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                @foreach (\App\Models\Booking::STATUSES as $status)
                                    <option value="{{ $status }}" @selected(old('status', $booking?->status ?? 'confirmed') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <small class="field-hint">Only Confirmed bookings take the rooms. Cancelled frees them again.</small>
                            @error('status')<span class="auth-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="price_sar">Price in SAR, per room per night</label>
                            <input id="price_sar" name="price_sar" type="number" step="0.01" min="0" max="100000" value="{{ old('price_sar', $booking?->price_sar) }}" required>
                            @error('price_sar')<span class="auth-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="reference">Reference (optional)</label>
                            <input id="reference" name="reference" maxlength="40" value="{{ old('reference', $booking?->reference) }}">
                            @error('reference')<span class="auth-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field" style="grid-column:1/-1">
                            <label for="notes">Notes (optional)</label>
                            <input id="notes" name="notes" maxlength="1000" value="{{ old('notes', $booking?->notes) }}">
                        </div>
                    </div>

                    <div style="margin-top:18px">
                        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Save booking' }}</button>
                    </div>
                @endif
            </form>
        </x-panel>
    @endif

    @push('scripts')
        <script src="{{ asset('js/booking-form.js') }}" defer></script>
    @endpush
</x-app-layout>
