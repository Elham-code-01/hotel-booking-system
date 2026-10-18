<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Make;

class UserAdminTest extends FeatureTestCase
{
    public function test_an_administrator_cannot_remove_their_own_access(): void
    {
        $admin = Make::user('admin');

        $this->actingAs($admin)->patch('/users/'.$admin->id, ['role' => 'staff', 'status' => 'active'])->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_a_pending_account_is_approved_with_a_role(): void
    {
        $admin = Make::user('admin');
        $pending = Make::user('viewer', ['status' => 'pending']);

        $this->actingAs($admin)->patch('/users/'.$pending->id, ['role' => 'staff', 'status' => 'active']);

        $this->assertSame('active', $pending->fresh()->status);
        $this->assertSame('staff', $pending->fresh()->role);
    }

    public function test_a_representative_needs_a_hotel_and_other_roles_never_keep_one(): void
    {
        $admin = Make::user('admin');
        $hotel = Make::hotel();
        $user = Make::user('viewer');

        $this->actingAs($admin)->patch('/users/'.$user->id, ['role' => 'representative', 'status' => 'active'])->assertSessionHas('error');
        $this->assertSame('viewer', $user->fresh()->role);

        $this->actingAs($admin)->patch('/users/'.$user->id, ['role' => 'representative', 'status' => 'active', 'hotel_id' => $hotel->id]);
        $this->assertSame($hotel->id, $user->fresh()->hotel_id);

        $this->actingAs($admin)->patch('/users/'.$user->id, ['role' => 'staff', 'status' => 'active', 'hotel_id' => $hotel->id]);
        $this->assertNull($user->fresh()->hotel_id);
    }

    public function test_resetting_a_password_signs_the_user_out_and_forces_a_new_password(): void
    {
        $admin = Make::user('admin');
        $user = Make::user('staff');

        DB::table('sessions')->insert([
            'id' => 'test-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time(),
        ]);

        $this->actingAs($admin)->post('/users/'.$user->id.'/password')->assertSessionHas('temp_password');

        $temporary = session('temp_password')['password'];

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check($temporary, $user->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());

        // the temporary password is never written to the activity log
        $this->assertTrue(AuditLog::where('action', 'user.password_reset')->exists());
        $this->assertFalse(AuditLog::all()->contains(fn ($log) => str_contains((string) json_encode($log->details), $temporary)));
    }

    public function test_a_user_with_a_temporary_password_can_only_reach_the_profile_page(): void
    {
        $user = Make::user('staff', ['must_change_password' => true]);

        $this->actingAs($user)->get('/dashboard')->assertRedirectToRoute('profile.edit');
        $this->actingAs($user)->get('/payments')->assertRedirectToRoute('profile.edit');
        $this->actingAs($user)->get('/profile')->assertOk();

        $this->actingAs($user)->put('/profile/password', [
            'current_password'      => 'Password-12345',
            'password'              => 'Brand-New-Pass-99',
            'password_confirmation' => 'Brand-New-Pass-99',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_an_administrator_cannot_reset_their_own_password_here(): void
    {
        $admin = Make::user('admin');

        $this->actingAs($admin)->post('/users/'.$admin->id.'/password')->assertSessionHas('error');

        $this->assertFalse($admin->fresh()->must_change_password);
    }
}
