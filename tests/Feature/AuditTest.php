<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Tests\Support\Make;

class AuditTest extends FeatureTestCase
{
    public function test_signing_in_is_recorded(): void
    {
        $user = Make::user('staff');

        $this->post('/login', ['email' => $user->email, 'password' => 'Password-12345']);

        $this->assertTrue(AuditLog::where('action', 'login')->where('user_id', $user->id)->exists());
    }

    public function test_a_failed_sign_in_is_recorded_without_the_password(): void
    {
        $user = Make::user('staff');

        $this->post('/login', ['email' => $user->email, 'password' => 'my-secret-guess']);

        $log = AuditLog::where('action', 'login.failed')->first();

        $this->assertNotNull($log);
        $this->assertSame($user->email, $log->details['email']);
        $this->assertStringNotContainsString('my-secret-guess', (string) json_encode($log->details));
    }

    public function test_a_new_booking_is_recorded(): void
    {
        $staff = Make::user('staff');
        $hotel = Make::hotel();
        $room = Make::rooms($hotel, 101, 101)->first();

        $this->actingAs($staff)->post('/bookings', [
            'hotel_id' => $hotel->id, 'company_id' => Make::company()->id,
            'check_in' => today()->addDays(5)->toDateString(), 'check_out' => today()->addDays(7)->toDateString(),
            'status' => 'confirmed', 'price_sar' => 100, 'rooms' => [$room->id],
        ]);

        $this->assertTrue(AuditLog::where('action', 'booking.created')->where('user_id', $staff->id)->exists());
    }

    public function test_only_an_administrator_can_read_the_activity_log(): void
    {
        $this->actingAs(Make::user('staff'))->get('/audit')->assertForbidden();
        $this->actingAs(Make::user('admin'))->get('/audit')->assertOk();
    }
}
