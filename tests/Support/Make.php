<?php

namespace Tests\Support;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/** Small helpers that create test data. The password of every test user is Password-12345. */
class Make
{
    private static int $counter = 0;

    public static function user(string $role = 'staff', array $extra = []): User
    {
        self::$counter++;

        return User::forceCreate(array_merge([
            'name'              => 'User '.self::$counter,
            'email'             => 'user'.self::$counter.'@example.test',
            'password'          => Hash::make('Password-12345'),
            'role'              => $role,
            'status'            => 'active',
            'email_verified_at' => now(),
        ], $extra));
    }

    public static function hotel(string $name = 'Test Hotel', string $city = 'Makkah'): Hotel
    {
        return Hotel::create(['name' => $name, 'city' => $city, 'is_active' => true]);
    }

    /** @return Collection<int, Room> */
    public static function rooms(Hotel $hotel, int $from, int $to, int $floor = 1, int $beds = 4): Collection
    {
        $rooms = collect();
        for ($number = $from; $number <= $to; $number++) {
            $rooms->push($hotel->rooms()->create(['room_number' => (string) $number, 'floor' => $floor, 'beds' => $beds]));
        }

        return $rooms;
    }

    public static function company(?string $name = null): Company
    {
        self::$counter++;

        return Company::create(['name' => $name ?? 'Company '.self::$counter]);
    }

    /** @param array<int, int> $roomIds */
    public static function booking(Hotel $hotel, Company $company, string $from, string $to, array $roomIds, string $status = 'confirmed', float $price = 100.0, float $rate = 3.75): Booking
    {
        $booking = new Booking([
            'hotel_id'   => $hotel->id,
            'company_id' => $company->id,
            'check_in'   => $from,
            'check_out'  => $to,
            'status'     => $status,
            'price_sar'  => $price,
        ]);
        $booking->forceFill(['exchange_rate' => $rate])->save();
        $booking->rooms()->sync($roomIds);

        return $booking;
    }
}
