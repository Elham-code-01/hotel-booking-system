<x-app-layout>
    <x-slot name="header">Settings</x-slot>

    <x-page-header title="Settings" sub="System values used in every calculation." />

    <x-panel title="Exchange rate">
        <form method="POST" action="{{ route('settings.update') }}" class="form-grid">
            @csrf
            @method('PATCH')
            <div class="field">
                <label for="exchange_rate">Saudi riyal (SAR) for 1 US dollar</label>
                <input id="exchange_rate" name="exchange_rate" type="number" step="0.01" min="0.01" max="100" value="{{ old('exchange_rate', $rate) }}" required>
                @error('exchange_rate')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div><button class="btn btn-primary" type="submit">Save rate</button></div>
        </form>
        <p class="muted" style="margin-top:10px">A new rate is used for bookings and charges saved from now on. Old records keep the rate they were saved with.</p>
    </x-panel>
</x-app-layout>
