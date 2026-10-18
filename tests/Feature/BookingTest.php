<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingService;
use Tests\Support\Make;

class BookingTest extends FeatureTestCase
{
    private $staff;
    private $hotel;
    private $rooms;
    private $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = Make::user('staff');
        $this->hotel = Make::hotel();
        $this->rooms = Make::rooms($this->hotel, 101, 105);
        $this->company = Make::company('Al Test');

        Setting::put('exchange_rate', '4');
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'hotel_id'   => $this->hotel->id,
            'company_id' => $this->company->id,
            'check_in'   => today()->addDays(10)->toDateString(),
            'check_out'  => today()->addDays(14)->toDateString(),
            'status'     => 'confirmed',
            'price_sar'  => 100,
            'rooms'      => $this->rooms->take(3)->pluck('id')->all(),
        ], $over);
    }

    private function book(array $over = [])
    {
        return $this->actingAs($this->staff)->post('/bookings', $this->payload($over));
    }

    public function test_a_booking_keeps_its_rooms_and_the_rate_of_that_day(): void
    {
        $this->book()->assertRedirect(route('bookings.index', $this->hotel));

        $booking = Booking::first();

        $this->assertSame(3, $booking->rooms()->count());
        $this->assertEquals(4.0, (float) $booking->exchange_rate);
        $this->assertSame(4, $booking->nights());
        // 3 rooms x 4 nights x 100 SAR / 4 = 300 USD
        $this->assertEquals(300.0, $booking->totalUsd());
    }

    public function test_changing_the_rate_later_does_not_change_an_old_booking(): void
    {
        $this->book();
        Setting::put('exchange_rate', '5');

        $this->assertEquals(300.0, Booking::first()->totalUsd());
    }

    public function test_a_room_cannot_be_confirmed_twice_for_overlapping_dates(): void
    {
        $this->book();

        $this->book(['check_in' => today()->addDays(12)->toDateString(), 'check_out' => today()->addDays(16)->toDateString()])
            ->assertSessionHasErrors('rooms');

        $this->assertSame(1, Booking::count());
    }

    public function test_a_room_can_change_hands_on_the_same_day(): void
    {
        $this->book();

        $this->book(['check_in' => today()->addDays(14)->toDateString(), 'check_out' => today()->addDays(17)->toDateString()])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
    }

    public function test_a_tentative_booking_does_not_take_the_rooms(): void
    {
        $this->book(['status' => 'tentative']);
        $this->book()->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
    }

    public function test_confirming_a_tentative_booking_checks_the_rooms_again(): void
    {
        $this->book(['status' => 'tentative']);
        $this->book();

        $tentative = Booking::where('status', 'tentative')->first();

        $this->actingAs($this->staff)
            ->patch('/bookings/'.$tentative->id, $this->payload(['status' => 'confirmed']))
            ->assertSessionHasErrors('rooms');

        $this->assertSame('tentative', $tentative->fresh()->status);
    }

    public function test_a_cancelled_booking_frees_its_rooms(): void
    {
        $this->book();
        $first = Booking::first();

        $this->actingAs($this->staff)->patch('/bookings/'.$first->id, $this->payload(['status' => 'cancelled']));
        $this->book()->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
    }

    public function test_free_rooms_exclude_confirmed_bookings_only(): void
    {
        $this->book();

        $free = app(BookingService::class)->freeRooms(
            $this->hotel,
            today()->addDays(10)->toDateString(),
            today()->addDays(14)->toDateString()
        );

        $this->assertSame(2, $free->count());
    }

    public function test_rooms_of_another_hotel_are_refused(): void
    {
        $otherRoom = Make::rooms(Make::hotel('Other', 'Madinah'), 201, 201)->first();

        $this->book(['rooms' => [$otherRoom->id]])->assertSessionHasErrors('rooms');

        $this->assertSame(0, Booking::count());
    }

    public function test_check_out_must_be_after_check_in(): void
    {
        $this->book(['check_out' => today()->addDays(10)->toDateString()])->assertSessionHasErrors('check_out');
    }

    public function test_a_viewer_cannot_create_a_booking(): void
    {
        $this->actingAs(Make::user('viewer'))->post('/bookings', $this->payload())->assertForbidden();

        $this->assertSame(0, Booking::count());
    }
}
