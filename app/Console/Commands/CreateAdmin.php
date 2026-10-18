<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first administrator on a live server (or resets an administrator's password).
 * The password is typed hidden on the screen. It is never written in a file or in the shell history.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email : Email address of the administrator}';

    protected $description = 'Create an administrator, or set a new password for an existing one';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (12+ characters, upper and lower case letters and a number)');
        $repeat = (string) $this->secret('Repeat the password');

        if ($password !== $repeat) {
            $this->error('The two passwords are not the same.');

            return self::FAILURE;
        }

        $check = Validator::make(['password' => $password], ['password' => ['required', Password::min(12)->mixedCase()->numbers()]]);

        if ($check->fails()) {
            foreach ($check->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        $user->forceFill([
            'name'                 => $user->name ?: 'Administrator',
            'password'             => Hash::make($password),
            'role'                 => 'admin',
            'status'               => 'active',
            'hotel_id'             => null,
            'must_change_password' => false,
            'email_verified_at'    => $user->email_verified_at ?? now(),
        ])->save();

        Audit::record($isNew ? 'admin.created' : 'admin.password_set_from_server', ['email' => $email]);

        $this->info(($isNew ? 'Administrator created: ' : 'Password set for: ').$email);

        return self::SUCCESS;
    }
}
