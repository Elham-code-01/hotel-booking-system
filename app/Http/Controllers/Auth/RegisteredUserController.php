<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller implements HasMiddleware
{
    /** At most 5 registration attempts per 10 minutes. */
    public static function middleware(): array
    {
        return [new Middleware('throttle:5,10', only: ['store'])];
    }

    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * A new account is always "viewer" + "pending".
     * It cannot sign in until an administrator approves it and chooses the role.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        (new User())->forceFill([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'viewer',
            'status'   => 'pending',
        ])->save();

        return redirect()->route('login')->with(
            'status',
            'Your request was sent. An administrator must approve your account before you can sign in.'
        );
    }
}
