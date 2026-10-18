<?php

namespace Tests\Feature;

use App\Models\OtherCharge;
use App\Models\Payment;
use App\Services\AccountService;
use Tests\Support\Make;

class AccountsTest extends FeatureTestCase
{
    private function totals($company): array
    {
        return app(AccountService::class)->totalsByCompany(collect([$company]))[$company->id];
    }

    private function pay($company, float $amount, string $date): void
    {
        (new Payment(['company_id' => $company->id, 'paid_on' => $date, 'amount_usd' => $amount]))->save();
    }

    public function test_the_balance_is_charged_minus_paid(): void
    {
        $hotel = Make::hotel();
        $rooms = Make::rooms($hotel, 101, 105);
        $company = Make::company();

        // 3 rooms x 4 nights x 100 SAR / 3.75 = 320 USD
        Make::booking($hotel, $company, '2030-01-01', '2030-01-05', $rooms->take(3)->pluck('id')->all(), 'confirmed', 100, 3.75);
        // 2 rooms x 2 nights x 100 SAR / 4 = 100 USD (a different rate that day)
        Make::booking($hotel, $company, '2030-02-01', '2030-02-03', $rooms->take(2)->pluck('id')->all(), 'confirmed', 100, 4.0);

        $this->pay($company, 200, '2030-01-02');
        $this->pay($company, -50, '2030-01-03'); // a refund

        $totals = $this->totals($company);

        $this->assertEquals(420.0, $totals['charged']);
        $this->assertEquals(150.0, $totals['paid']);
        $this->assertEquals(270.0, $totals['balance']);
    }

    public function test_only_confirmed_bookings_are_charged(): void
    {
        $hotel = Make::hotel();
        $rooms = Make::rooms($hotel, 101, 102);
        $company = Make::company();

        Make::booking($hotel, $company, '2030-01-01', '2030-01-03', [$rooms[0]->id], 'tentative');
        Make::booking($hotel, $company, '2030-01-01', '2030-01-03', [$rooms[1]->id], 'cancelled');

        $this->assertEquals(0.0, $this->totals($company)['charged']);
    }

    public function test_other_charges_are_per_night_or_once(): void
    {
        $company = Make::company();

        // 5 x 3 nights x 15 SAR / 3.75 = 60 USD
        (new OtherCharge(['company_id' => $company->id, 'charged_on' => '2030-01-01', 'description' => 'Nusuk', 'charge_type' => 'nightly', 'quantity' => 5, 'nights' => 3, 'price_sar' => 15]))->save();
        // 5 x once x 15 SAR / 3.75 = 20 USD
        (new OtherCharge(['company_id' => $company->id, 'charged_on' => '2030-01-01', 'description' => 'Visa', 'charge_type' => 'once', 'quantity' => 5, 'nights' => 3, 'price_sar' => 15]))->save();

        $this->assertEquals(80.0, $this->totals($company)['other']);
    }

    public function test_the_statement_page_shows_the_payments_with_their_dates(): void
    {
        $company = Make::company('Statement Co');
        $this->pay($company, 3000, '2030-01-10');
        $this->pay($company, 4800, '2030-01-20');

        $this->actingAs(Make::user('staff'))->get('/company-account?company_id='.$company->id)
            ->assertOk()
            ->assertSee('2030-01-10')
            ->assertSee('2030-01-20')
            ->assertSee('$7,800.00');
    }

    public function test_payments_cannot_be_dated_in_the_future_or_be_zero(): void
    {
        $staff = Make::user('staff');
        $company = Make::company();

        $this->actingAs($staff)->post('/payments', ['company_id' => $company->id, 'paid_on' => today()->addDay()->toDateString(), 'amount_usd' => 100])
            ->assertSessionHasErrors('paid_on');

        $this->actingAs($staff)->post('/payments', ['company_id' => $company->id, 'paid_on' => today()->toDateString(), 'amount_usd' => 0])
            ->assertSessionHasErrors('amount_usd');
    }

    public function test_only_an_administrator_can_delete_a_payment(): void
    {
        $company = Make::company();
        $this->pay($company, 100, today()->toDateString());
        $payment = Payment::first();

        $this->actingAs(Make::user('staff'))->delete('/payments/'.$payment->id)->assertForbidden();
        $this->assertSame(1, Payment::count());

        $this->actingAs(Make::user('admin'))->delete('/payments/'.$payment->id);
        $this->assertSame(0, Payment::count());
    }
}
