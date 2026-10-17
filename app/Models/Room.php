<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{
    protected $fillable = ['hotel_id', 'room_number', 'floor', 'beds', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'floor' => 'integer', 'beds' => 'integer'];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_room')
            ->withPivot(['operational_status', 'occupied_at', 'checked_out_at', 'marked_by'])
            ->withTimestamps();
    }
}
