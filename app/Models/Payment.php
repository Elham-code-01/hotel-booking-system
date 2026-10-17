<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    // created_by is NOT fillable: the server sets it.
    protected $fillable = ['company_id', 'paid_on', 'amount_usd', 'method', 'reference', 'note'];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount_usd' => 'decimal:2'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
