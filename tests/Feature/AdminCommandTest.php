<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminCommandTest extends FeatureTestCase
{
    private const ASK_PASSWORD = 'Password (12+ characters, upper and lower case letters and a number)';
    private const ASK_REPEAT = 'Repeat the password';

    public function test_it_creates_an_active_administrator(): void
    {
        $this->artisan('admin:create', ['email' => 'Boss@Example.test'])
            ->expectsQuestion(self::ASK_PASSWORD, 'Strong-Pass-12345')
            ->expectsQuestion(self::ASK_REPEAT, 'Strong-Pass-12345')
            ->assertSuccessful();

        $user = User::where('email', 'boss@example.test')->first();

        $this->assertSame('admin', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('Strong-Pass-12345', $user->password));
    }

    public function test_a_weak_password_is_refused(): void
    {
        $this->artisan('admin:create', ['email' => 'boss@example.test'])
            ->expectsQuestion(self::ASK_PASSWORD, 'short')
            ->expectsQuestion(self::ASK_REPEAT, 'short')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'boss@example.test']);
    }

    public function test_two_different_passwords_are_refused(): void
    {
        $this->artisan('admin:create', ['email' => 'boss@example.test'])
            ->expectsQuestion(self::ASK_PASSWORD, 'Strong-Pass-12345')
            ->expectsQuestion(self::ASK_REPEAT, 'Strong-Pass-99999')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'boss@example.test']);
    }
}
