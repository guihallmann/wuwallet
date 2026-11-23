<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'is_active',
        'price_updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'current_price' => 'decimal:2',
        'is_active' => 'boolean',
        'price_updated_at' => 'datetime',
    ];

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function scopeTradable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('current_price', '>', 0)
            ->where('ticker', 'not like', '%F');
    }
}
