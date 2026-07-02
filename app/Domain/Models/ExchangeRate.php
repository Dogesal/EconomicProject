<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasUuids;

    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'effective_on',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'effective_on' => 'date',
        ];
    }
}
