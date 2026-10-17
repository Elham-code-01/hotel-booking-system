<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="eyebrow">Operations desk</div>
        <h2>Sign in</h2>
        <p class="muted">Use the account approved by your administrator.</p>

        @if (session('status'))
            <div class="notice info auth-notice">{{ session('status') }}</div>
        @endif

        <div class="field auth-field">
            <label for="email">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            @error('email')
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field auth-field">
            <label for="password">Password</label>
            <div class="password-control">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="btn password-toggle" type="button" data-action="show-password" data-target="password" aria-controls="password" aria-pressed="false">Show</button>
            </div>
            @error('password')
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <label class="auth-check">
            <input type="checkbox" name="remember" value="1"> Keep me signed in on this device
        </label>

        <button class="btn btn-primary auth-submit" type="submit">Sign in</button>

        @if (Route::has('register'))
            <p class="auth-switch">New to the team? <a href="{{ route('register') }}">Request an account</a></p>
        @endif
    </form>
</x-guest-layout>
