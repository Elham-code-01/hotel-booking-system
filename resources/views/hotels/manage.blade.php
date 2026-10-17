<x-app-layout>
    <x-slot name="header">{{ $hotel->name }}</x-slot>

    <x-page-header :title="$hotel->name" :sub="$hotel->city.' · details and rooms'">
        <a class="btn" href="{{ route('hotels', ['location' => $hotel->city, 'hotel' => $hotel->id]) }}">← Back to the hotel</a>
    </x-page-header>

    <x-panel title="Hotel details">
        <form method="POST" action="{{ route('hotels.update', $hotel) }}" class="form-grid">
            @csrf
            @method('PATCH')

            <div class="field">
                <label for="name">Hotel name</label>
                <input id="name" name="name" value="{{ old('name', $hotel->name) }}" maxlength="100" required>
                @error('name')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="city">Location (city)</label>
                <input id="city" name="city" list="cities" value="{{ old('city', $hotel->city) }}" maxlength="60" required>
                <datalist id="cities">@foreach ($cities as $city)<option value="{{ $city }}">@endforeach</datalist>
                @error('city')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="address">Address</label>
                <input id="address" name="address" value="{{ old('address', $hotel->address) }}" maxlength="255">
            </div>
            <div class="field">
                <label for="representative_name">Hotel representative</label>
                <input id="representative_name" name="representative_name" value="{{ old('representative_name', $hotel->representative_name) }}" maxlength="100">
            </div>
            <div class="field">
                <label for="contact">Contact</label>
                <input id="contact" name="contact" value="{{ old('contact', $hotel->contact) }}" maxlength="100">
            </div>
            <div class="field">
                <label for="is_active">Status</label>
                <label class="auth-check"><input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $hotel->is_active))> Active (shown on the Hotels page and in new bookings)</label>
                @error('is_active')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div><button class="btn btn-primary" type="submit">Save details</button></div>
        </form>
    </x-panel>

    <x-panel title="Add rooms">
        <form method="POST" action="{{ route('rooms.store', $hotel) }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="floor">Floor</label>
                <input id="floor" name="floor" type="number" min="0" max="30" value="{{ old('floor', 1) }}" required>
                @error('floor')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="beds">Beds in each room</label>
                <input id="beds" name="beds" type="number" min="1" max="12" value="{{ old('beds', 4) }}" required>
                @error('beds')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="from">Room numbers from</label>
                <input id="from" name="from" type="number" min="1" max="9999" value="{{ old('from') }}" placeholder="101" required>
                @error('from')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="to">to</label>
                <input id="to" name="to" type="number" min="1" max="9999" value="{{ old('to') }}" placeholder="107" required>
                @error('to')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div><button class="btn btn-primary" type="submit">Add rooms</button></div>
        </form>
        <p class="muted" style="margin-top:10px">For one room, type the same number in both boxes. Numbers that already exist are skipped.</p>
    </x-panel>

    @forelse ($floors as $floor => $rooms)
        <x-panel :title="'Floor '.$floor.' · '.$rooms->count().' rooms'">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Room</th><th>Details</th></tr></thead>
                    <tbody>
                        @foreach ($rooms as $room)
                            <tr>
                                <td><strong>{{ $room->room_number }}</strong> @unless ($room->is_active) <span class="muted">(inactive)</span> @endunless</td>
                                <td>
                                    <form method="POST" action="{{ route('rooms.update', $room) }}" class="toolbar" style="margin:0">
                                        @csrf
                                        @method('PATCH')
                                        <label class="muted">Floor <input name="floor" type="number" min="0" max="30" value="{{ $room->floor }}" style="width:70px"></label>
                                        <label class="muted">Beds <input name="beds" type="number" min="1" max="12" value="{{ $room->beds }}" style="width:70px"></label>
                                        <label class="auth-check" style="margin:0"><input type="checkbox" name="is_active" value="1" @checked($room->is_active)> Active</label>
                                        <button class="btn btn-sm" type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    @empty
        <x-panel title="Rooms">
            <x-empty-state title="No rooms yet" icon="▤">Use “Add rooms” above to register the rooms of this hotel.</x-empty-state>
        </x-panel>
    @endforelse
</x-app-layout>
