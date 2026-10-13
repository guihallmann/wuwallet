<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetType;
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
        'asset_type',
        'current_price',
        'price_updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'asset_type' => AssetType::class,
        'current_price' => 'decimal:2',
        'price_updated_at' => 'datetime',
    ];
}
