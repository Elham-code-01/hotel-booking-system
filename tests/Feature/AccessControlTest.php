<?php

namespace Tests\Feature;

use Tests\Support\Make;

class AccessControlTest extends FeatureTestCase
{
    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['/dashboard', '/hotels', '/payments', '/reports', '/users', '/audit', '/settings'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_pending_and_blocked_accounts_cannot_use_the_system(): void
    {
        foreach (['pending', 'blocked'] as $status) {
            $user = Make::user('staff', ['status' => $status]);

            $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_viewer_can_read_but_cannot_write_or_manage(): void
    {
        $hotel = Make::hotel();
        $viewer = Make::user('viewer');

        foreach (['/dashboard', '/hotels', '/accounts', '/company-account', '/reports', '/hotels/'.$hotel->id.'/bookings'] as $url) {
            $this->actingAs($viewer)->get($url)->assertOk();
        }

        foreach (['/payments', '/other-charges', '/bookings/new', '/representative', '/users', '/audit', '/settings', '/hotels/new'] as $url) {
            $this->actingAs($viewer)->get($url)->assertForbidden();
        }
    }

    public function test_staff_can_work_but_cannot_use_admin_pages(): void
    {
        $staff = Make::user('staff');

        foreach (['/dashboard', '/hotels', '/bookings/new', '/payments', '/other-charges', '/companies', '/accounts', '/reports', '/representative'] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }

        foreach (['/users', '/audit', '/settings', '/hotels/new'] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_representative_sees_only_the_representative_page(): void
    {
        $hotel = Make::hotel();
        $rep = Make::user('representative', ['hotel_id' => $hotel->id]);

        $this->actingAs($rep)->get('/dashboard')->assertRedirectToRoute('representative');
        $this->actingAs($rep)->get('/representative')->assertOk();

        foreach (['/payments', '/reports', '/hotels', '/accounts', '/companies', '/bookings/new', '/users'] as $url) {
            $this->actingAs($rep)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_open_every_page(): void
    {
        $hotel = Make::hotel();
        $admin = Make::user('admin');

        $urls = [
            '/dashboard', '/hotels', '/hotels/new', '/hotels/'.$hotel->id.'/manage', '/hotels/'.$hotel->id.'/bookings',
            '/bookings/new', '/representative', '/companies', '/accounts', '/company-account', '/payments',
            '/other-charges', '/reports', '/users', '/audit', '/settings', '/profile',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
