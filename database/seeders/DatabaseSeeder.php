<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password
            || $email === 'change-me@example.com'
            || $password === 'change-this-to-a-long-strong-password'
            || strlen($password) < 12) {
            throw new RuntimeException(
                'Set your own ADMIN_EMAIL and a strong ADMIN_PASSWORD (12+ characters) in .env, then run again.'
            );
        }

        Hotel::firstOrCreate(['name' => 'Makkah'], ['city' => 'Makkah']);
        Hotel::firstOrCreate(['name' => 'Madinah'], ['city' => 'Madinah']);

        Setting::firstOrCreate(['key' => 'exchange_rate'], ['value' => '3.75']);

        // forceFill: role/status can never be set from a web form.
        $admin = User::firstOrNew(['email' => $email]);
        $admin->forceFill([
            'name'              => 'Admin',
            'password'          => Hash::make($password),
            'role'              => 'admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ])->save();
    }
}
