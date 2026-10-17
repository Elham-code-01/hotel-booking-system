<x-app-layout>
    <x-slot name="header">All companies</x-slot>

    <x-page-header title="All companies" sub="The companies that book rooms. Account totals are added in a later step." />

    @if ($canWrite)
        <x-panel title="Add a company">
            <form method="POST" action="{{ route('companies.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="name">Company name</label>
                    <input id="name" name="name" value="{{ old('name') }}" maxlength="150" required>
                    @error('name')<span class="auth-error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="50">
                    @error('phone')<span class="auth-error">{{ $message }}</span>@enderror
                </div>
                <div class="field" style="grid-column:1/-1">
                    <label for="notes">Notes</label>
                    <input id="notes" name="notes" value="{{ old('notes') }}" maxlength="1000">
                </div>
                <div><button class="btn btn-primary" type="submit">Add company</button></div>
            </form>
        </x-panel>
    @endif

    <x-panel :title="'Companies · '.$companies->count()">
        @if ($companies->isEmpty())
            <x-empty-state title="No companies yet" icon="▤">Add the first company above.</x-empty-state>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Company</th><th>Phone</th><th>Notes</th><th>Bookings</th>@if ($canWrite)<th>Actions</th>@endif</tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                @if ($canWrite)
                                    <td><input form="company-{{ $company->id }}" name="name" value="{{ $company->name }}" maxlength="150" required aria-label="Company name"></td>
                                    <td><input form="company-{{ $company->id }}" name="phone" value="{{ $company->phone }}" maxlength="50" aria-label="Phone"></td>
                                    <td><input form="company-{{ $company->id }}" name="notes" value="{{ $company->notes }}" maxlength="1000" aria-label="Notes"></td>
                                    <td>{{ $company->bookings_count }}</td>
                                    <td>
                                        <form id="company-{{ $company->id }}" method="POST" action="{{ route('companies.update', $company) }}" style="display:inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm" type="submit">Save</button>
                                        </form>
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('companies.destroy', $company) }}" style="display:inline" onsubmit="return confirm('Delete {{ e($company->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                @else
                                    <td><strong>{{ $company->name }}</strong></td>
                                    <td>{{ $company->phone }}</td>
                                    <td>{{ $company->notes }}</td>
                                    <td>{{ $company->bookings_count }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
</x-app-layout>
