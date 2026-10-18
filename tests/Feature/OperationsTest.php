<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\Make;

class OperationsTest extends FeatureTestCase
{
    private $hotel;
    private $other;
    private $rooms;
    private $rep;
    private $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Make::hotel('First Hotel');
        $this->other = Make::hotel('Second Hotel', 'Madinah');
        $this->rooms = Make::rooms($this->hotel, 101, 104);
        $this->rep = Make::user('representative', ['hotel_id' => $this->hotel->id]);

        $this->booking = Make::booking(
            $this->hotel, Make::company('Own Co'),
            today()->toDateString(), today()->addDays(3)->toDateString(),
            $this->rooms->take(3)->pluck('id')->all()
        );
    }

    private function mark($user, $booking, string $action, array $roomIds)
    {
        return $this->actingAs($user)->post('/representative/rooms', [
            'booking_id' => $booking->id, 'action' => $action, 'rooms' => $roomIds,
        ]);
    }

    private function state($booking, int $roomId): string
    {
        return (string) DB::table('booking_room')->where('booking_id', $booking->id)->where('room_id', $roomId)->value('operational_status');
    }

    public function test_the_representative_walks_a_room_through_its_whole_cycle(): void
    {
        $roomId = $this->rooms[0]->id;

        $this->mark($this->rep, $this->booking, 'occupied', [$roomId]);
        $this->assertSame('occupied', $this->state($this->booking, $roomId));

        $this->mark($this->rep, $this->booking, 'checked_out', [$roomId]);
        $this->assertSame('checked_out', $this->state($this->booking, $roomId));

        $this->mark($this->rep, $this->booking, 'reset', [$roomId]);
        $this->assertSame('booked', $this->state($this->booking, $roomId));
    }

    public function test_a_room_cannot_be_checked_out_before_it_was_occupied(): void
    {
        $roomId = $this->rooms[0]->id;

        $this->mark($this->rep, $this->booking, 'checked_out', [$roomId])->assertSessionHas('error');

        $this->assertSame('booked', $this->state($this->booking, $roomId));
    }

    public function test_a_booking_of_a_future_day_cannot_be_marked_occupied(): void
    {
        $future = Make::booking(
            $this->hotel, Make::company(),
            today()->addDay()->toDateString(), today()->addDays(4)->toDateString(),
            [$this->rooms[3]->id]
        );

        $this->mark($this->rep, $future, 'occupied', [$this->rooms[3]->id])->assertSessionHasErrors('rooms');

        $this->assertSame('booked', $this->state($future, $this->rooms[3]->id));
    }

    public function test_a_representative_cannot_touch_another_hotel(): void
    {
        $room = Make::rooms($this->other, 201, 201)->first();
        $foreign = Make::booking($this->other, Make::company(), today()->toDateString(), today()->addDays(2)->toDateString(), [$room->id]);

        $this->mark($this->rep, $foreign, 'occupied', [$room->id])->assertForbidden();

        $this->assertSame('booked', $this->state($foreign, $room->id));
    }

    public function test_the_representative_page_shows_only_the_own_hotel(): void
    {
        $room = Make::rooms($this->other, 201, 201)->first();
        Make::booking($this->other, Make::company('Foreign Co'), today()->toDateString(), today()->addDays(2)->toDateString(), [$room->id]);

        $this->actingAs($this->rep)->get('/representative')
            ->assertOk()
            ->assertSee('Own Co')
            ->assertDontSee('Foreign Co');
    }

    public function test_a_room_with_guests_cannot_be_removed_from_its_booking(): void
    {
        $staff = Make::user('staff');
        $ids = $this->rooms->take(3)->pluck('id')->all();

        $this->mark($staff, $this->booking, 'occupied', [$ids[0]]);

        $this->actingAs($staff)->patch('/bookings/'.$this->booking->id, [
            'hotel_id'   => $this->hotel->id,
            'company_id' => $this->booking->company_id,
            'check_in'   => today()->toDateString(),
            'check_out'  => today()->addDays(3)->toDateString(),
            'status'     => 'confirmed',
            'price_sar'  => 100,
            'rooms'      => [$ids[1], $ids[2]],
        ])->assertSessionHasErrors('rooms');

        $this->assertSame(3, $this->booking->rooms()->count());
    }

    public function test_a_booking_with_guests_inside_cannot_be_cancelled(): void
    {
        $staff = Make::user('staff');
        $ids = $this->rooms->take(3)->pluck('id')->all();

        $this->mark($staff, $this->booking, 'occupied', [$ids[0]]);

        $this->actingAs($staff)->patch('/bookings/'.$this->booking->id, [
            'hotel_id'   => $this->hotel->id,
            'company_id' => $this->booking->company_id,
            'check_in'   => today()->toDateString(),
            'check_out'  => today()->addDays(3)->toDateString(),
            'status'     => 'cancelled',
            'price_sar'  => 100,
            'rooms'      => $ids,
        ])->assertSessionHasErrors('rooms');

        $this->assertSame('confirmed', $this->booking->fresh()->status);
    }
}
