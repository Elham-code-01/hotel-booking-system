<x-app-layout>
    <x-slot name="header">Other charges</x-slot>

    <x-page-header title="Other charges" sub="Charges that are not room bookings, for example the Nusuk fee." />

    <x-panel title="Add a charge">
        <form method="POST" action="{{ route('other-charges.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="company_id">Company</label>
                <select id="company_id" name="company_id" required>
                    <option value="">Choose a company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id') === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                @error('company_id')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="hotel_id">Hotel (optional)</label>
                <select id="hotel_id" name="hotel_id">
                    <option value="">Not tied to a hotel</option>
                    @foreach ($hotels as $hotel)
                        <option value="{{ $hotel->id }}" @selected((int) old('hotel_id') === $hotel->id)>{{ $hotel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="charged_on">Date</label>
                <input id="charged_on" name="charged_on" type="date" value="{{ old('charged_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                @error('charged_on')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="description">Description</label>
                <input id="description" name="description" maxlength="255" value="{{ old('description') }}" placeholder="Nusuk fee" required>
                @error('description')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="charge_type">Charged</label>
                <select id="charge_type" name="charge_type">
                    <option value="nightly" @selected(old('charge_type', 'nightly') === 'nightly')>Per unit, per night</option>
                    <option value="once" @selected(old('charge_type') === 'once')>Once</option>
                </select>
            </div>
            <div class="field">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" max="100000" value="{{ old('quantity', 1) }}" required>
                @error('quantity')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="nights">Nights (ignored when “Once”)</label>
                <input id="nights" name="nights" type="number" min="1" max="366" value="{{ old('nights', 1) }}">
                @error('nights')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="price_sar">Price in SAR</label>
                <input id="price_sar" name="price_sar" type="number" step="0.01" min="0" max="100000" value="{{ old('price_sar') }}" required>
                @error('price_sar')<span class="auth-error">{{ $message }}</span>@enderror
            </div>
            <div><button class="btn btn-primary" type="submit">Save charge</button></div>
        </form>
    </x-panel>

    <x-panel title="Charges">
        @if ($charges->isEmpty())
            <x-empty-state title="No extra charges yet" icon="＋">Saved charges appear here.</x-empty-state>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Company</th><th>Hotel</th><th>Description</th><th>Qty</th><th>Nights</th><th>SAR</th><th>Total USD</th>@if ($canDelete)<th></th>@endif</tr></thead>
                    <tbody>
                        @foreach ($charges as $charge)
                            <tr>
                                <td>{{ $charge->charged_on->format('Y-m-d') }}</td>
                                <td><strong>{{ $charge->company->name }}</strong></td>
                                <td>{{ $charge->hotel?->name }}</td>
                                <td>{{ $charge->description }}</td>
                                <td>{{ $charge->quantity }}</td>
                                <td>{{ $charge->charge_type === 'once' ? 'once' : $charge->nights }}</td>
                                <td>{{ number_format((float) $charge->price_sar, 2) }}</td>
                                <td><strong><x-money :value="$charge->totalUsd()" /></strong></td>
                                @if ($canDelete)
                                    <td>
                                        <form method="POST" action="{{ route('other-charges.destroy', $charge) }}" onsubmit="return confirm('Delete this charge?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px">{{ $charges->links() }}</div>
        @endif
    </x-panel>
</x-app-layout>
