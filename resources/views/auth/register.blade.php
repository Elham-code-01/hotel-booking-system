<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="eyebrow">Team access</div>
        <h2>Request an account</h2>
        <p class="muted">An administrator must approve your account before you can sign in.</p>

        <div class="field auth-field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="100" required autofocus>
            @error('name')
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field auth-field">
            <label for="email">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
            @error('email')
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field auth-field">
            <label for="password">Password</label>
            <div class="password-control">
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
                <button class="btn password-toggle" type="button" data-action="show-password" data-target="password" aria-controls="password" aria-pressed="false">Show</button>
            </div>
            <small class="field-hint">At least 12 characters, with upper and lower case letters and a number.</small>
            @error('password')
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field auth-field">
            <label for="password_confirmation">Confirm password</label>
            <div class="password-control">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                <button class="btn password-toggle" type="button" data-action="show-password" data-target="password_confirmation" aria-controls="password_confirmation" aria-pressed="false">Show</button>
            </div>
        </div>

        <button class="btn btn-primary auth-submit" type="submit">Send request</button>
        <p class="auth-switch">Already approved? <a href="{{ route('login') }}">Return to sign in</a></p>
    </form>
</x-guest-layout>
