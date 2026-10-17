<x-app-layout>
    <x-slot name="header">My profile</x-slot>

    <x-page-header title="My profile" sub="Change your name, email or password." />

    <div class="grid cols-2">
        <x-panel title="Details">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="100" required>
                    @error('name')<span class="auth-error">{{ $message }}</span>@enderror
                </div>

                <div class="field" style="margin-top:14px">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<span class="auth-error">{{ $message }}</span>@enderror
                </div>

                <button class="btn btn-primary" type="submit" style="margin-top:18px">Save details</button>
            </form>
        </x-panel>

        <x-panel title="Password">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="current_password">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password', 'password')<span class="auth-error">{{ $message }}</span>@enderror
                </div>

                <div class="field" style="margin-top:14px">
                    <label for="new_password">New password</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" minlength="12" required>
                    <small class="field-hint">At least 12 characters, with upper and lower case letters and a number.</small>
                    @error('password', 'password')<span class="auth-error">{{ $message }}</span>@enderror
                </div>

                <div class="field" style="margin-top:14px">
                    <label for="new_password_confirmation">Confirm new password</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>

                <button class="btn btn-primary" type="submit" style="margin-top:18px">Change password</button>
            </form>
        </x-panel>
    </div>
</x-app-layout>
