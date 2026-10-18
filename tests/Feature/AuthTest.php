<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Support\Make;

class AuthTest extends FeatureTestCase
{
    public function test_registration_creates_a_pending_viewer_even_if_the_form_asks_for_more(): void
    {
        $this->post('/register', [
            'name'                  => 'New Person',
            'email'                 => 'new@example.test',
            'password'              => 'Strong-Pass-123',
            'password_confirmation' => 'Strong-Pass-123',
            'role'                  => 'admin',
            'status'                => 'active',
        ])->assertRedirect('/login');

        $user = User::where('email', 'new@example.test')->first();

        $this->assertSame('viewer', $user->role);
        $this->assertSame('pending', $user->status);
        $this->assertGuest();
    }

    public function test_a_weak_password_is_rejected(): void
    {
        $this->post('/register', [
            'name'                  => 'Weak',
            'email'                 => 'weak@example.test',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
    }

    public function test_a_pending_account_cannot_sign_in_and_is_told_why(): void
    {
        $user = Make::user('viewer', ['status' => 'pending']);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Password-12345']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('waiting', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_an_active_account_can_sign_in(): void
    {
        $user = Make::user('staff');

        $this->post('/login', ['email' => $user->email, 'password' => 'Password-12345'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_never_signs_in_and_too_many_tries_lock_the_account(): void
    {
        $user = Make::user('staff');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'Password-12345'])->assertSessionHasErrors('email');

        $this->assertStringContainsString('Too many', session('errors')->first('email'));
        $this->assertGuest();
    }
}
