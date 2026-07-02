<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimals',
    ];

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
        ];
    }
}
