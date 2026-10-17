<x-app-layout>
    <x-slot name="header">Add hotel</x-slot>

    <x-page-header title="Add hotel" sub="Create a hotel, then add its rooms.">
        <a class="btn" href="{{ route('hotels') }}">← Back to hotels</a>
    </x-page-header>

    <x-panel title="Hotel details">
        <form method="POST" action="{{ route('hotels.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="name">Hotel name</label>
                <input id="name" name="name" value="{{ old('name') }}" maxlength="100" required>
                @error('name')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="city">Location (city)</label>
                <input id="city" name="city" list="cities" value="{{ old('city') }}" maxlength="60" placeholder="Makkah" required>
                <datalist id="cities">@foreach ($cities as $city)<option value="{{ $city }}">@endforeach</datalist>
                @error('city')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="address">Address (optional)</label>
                <input id="address" name="address" value="{{ old('address') }}" maxlength="255">
            </div>
            <div class="field">
                <label for="representative_name">Hotel representative (optional)</label>
                <input id="representative_name" name="representative_name" value="{{ old('representative_name') }}" maxlength="100">
            </div>
            <div class="field">
                <label for="contact">Contact (optional)</label>
                <input id="contact" name="contact" value="{{ old('contact') }}" maxlength="100">
            </div>
            <div><button class="btn btn-primary" type="submit">Add hotel</button></div>
        </form>
        <p class="muted" style="margin-top:10px">After saving you add its rooms. The hotel then appears on the Hotels page under its location.</p>
    </x-panel>
</x-app-layout>
