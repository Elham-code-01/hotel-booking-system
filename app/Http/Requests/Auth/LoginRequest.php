<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Only "active" (admin-approved) accounts can sign in.
     * A pending account gets a clear message, but only after its password is correct.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password') + ['status' => 'active'];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            Audit::record('login.failed', ['email' => $this->string('email')->lower()->toString()]);

            $user = User::where('email', $this->string('email')->lower()->toString())->first();

            if ($user && $user->status === 'pending' && Hash::check($this->string('password')->toString(), $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'Your account is waiting for administrator approval.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        Audit::record('login.locked', ['email' => $this->string('email')->lower()->toString()]);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Too many sign-in attempts. Please try again in '.$seconds.' seconds.',
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
