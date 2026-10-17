<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_TENTATIVE = 'tentative';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_CONFIRMED, self::STATUS_TENTATIVE, self::STATUS_CANCELLED];

    // created_by and exchange_rate are NOT fillable: the server sets them, never a form.
    protected $fillable = ['reference', 'hotel_id', 'company_id', 'check_in', 'check_out', 'status', 'price_sar', 'notes'];

    protected function casts(): array
    {
        return [
            'check_in'      => 'date',
            'check_out'     => 'date',
            'price_sar'     => 'decimal:2',
            'exchange_rate' => 'decimal:4',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'booking_room')
            ->withPivot(['operational_status', 'occupied_at', 'checked_out_at', 'marked_by'])
            ->withTimestamps();
    }

    /** Cancelled bookings never block rooms and are never charged. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * The single place for the overlap rule:
     * check-in is included, check-out is excluded (same-day turnover allowed).
     */
    public function scopeOverlapping(Builder $query, $from, $to): Builder
    {
        return $query->where('check_in', '<', $to)->where('check_out', '>', $from);
    }

    public function nights(): int
    {
        return max(0, (int) $this->check_in->diffInDays($this->check_out));
    }

    public function roomCount(): int
    {
        return (int) ($this->rooms_count ?? $this->rooms()->count());
    }

    /** The single place for booking money: rooms x nights x price. */
    public function totalSar(): float
    {
        return round($this->roomCount() * $this->nights() * (float) $this->price_sar, 2);
    }

    public function totalUsd(): float
    {
        $rate = (float) $this->exchange_rate;

        return $rate > 0 ? round($this->totalSar() / $rate, 2) : 0.0;
    }
}
