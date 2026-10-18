<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRouteAccess;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Booking;
use App\Models\OtherCharge;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class SecurityTest extends FeatureTestCase
{
    public function test_every_page_has_the_browser_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_strict_transport_security_is_sent_on_https_only(): void
    {
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
        $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_sensitive_fields_can_never_be_mass_assigned(): void
    {
        foreach (['role', 'status', 'hotel_id', 'must_change_password'] as $field) {
            $this->assertFalse((new User())->isFillable($field), "User::{$field} must not be fillable");
        }

        foreach ([Booking::class, OtherCharge::class] as $class) {
            foreach (['created_by', 'exchange_rate'] as $field) {
                $this->assertFalse((new $class())->isFillable($field), "{$class}::{$field} must not be fillable");
            }
        }

        $this->assertFalse((new Payment())->isFillable('created_by'));
    }

    public function test_every_route_is_protected_and_has_an_access_rule(): void
    {
        $covered = collect(config('navigation.items'))->flatMap(fn ($item) => $item['routes'] ?? [$item['route']])->all();
        $open = ['login', 'register', 'logout', 'up'];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || in_array($name, $open, true) || preg_match('/^(storage\.|ignition\.|sanctum\.)/', $name)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth', $middleware, "Route {$name} is not behind sign-in");
            $this->assertContains(EnsureUserIsActive::class, $middleware, "Route {$name} does not check that the account is active");

            if (! str_starts_with($name, 'profile.')) {
                $this->assertContains($name, $covered, "Route {$name} has no access rule in config/navigation.php");
                $this->assertContains(EnsureRouteAccess::class, $middleware, "Route {$name} does not check the role");
            }
        }

        foreach (['login', 'register'] as $name) {
            $this->assertContains('guest', Route::getRoutes()->getByName($name)->gatherMiddleware());
        }
        $this->assertContains('auth', Route::getRoutes()->getByName('logout')->gatherMiddleware());
    }

    public function test_every_rule_in_the_config_points_to_a_real_route(): void
    {
        foreach (config('navigation.items') as $item) {
            foreach ($item['routes'] ?? [$item['route']] as $name) {
                $this->assertTrue(Route::has($name), "config/navigation.php names the route {$name}, which does not exist");
            }
        }
    }
}
