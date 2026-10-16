<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticker',
        'name',
        'sector',
        'current_price',
        'price_updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'current_price' => 'decimal:2',
        'price_updated_at' => 'datetime',
    ];
}
