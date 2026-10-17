<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherCharge extends Model
{
    public const TYPE_NIGHTLY = 'nightly';
    public const TYPE_ONCE = 'once';

    // created_by and exchange_rate are NOT fillable: the server sets them.
    protected $fillable = ['company_id', 'hotel_id', 'charged_on', 'description', 'charge_type', 'quantity', 'nights', 'price_sar'];

    protected function casts(): array
    {
        return [
            'charged_on'    => 'date',
            'price_sar'     => 'decimal:2',
            'exchange_rate' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /** The single place for extra-charge money: quantity x nights (or once) x price. */
    public function totalSar(): float
    {
        $nights = $this->charge_type === self::TYPE_ONCE ? 1 : (int) $this->nights;

        return round((int) $this->quantity * $nights * (float) $this->price_sar, 2);
    }

    public function totalUsd(): float
    {
        $rate = (float) $this->exchange_rate;

        return $rate > 0 ? round($this->totalSar() / $rate, 2) : 0.0;
    }
}
