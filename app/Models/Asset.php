<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    // B3 lists odd-lot (mercado fracionário) tickers as the base ticker plus this suffix; brapi has no flag for it.
    public const FRACTIONAL_SUFFIX = 'F';

    public const LOT_SIZE = 100;

    public const SECTORS_CACHE_KEY = 'asset_sectors_v3';

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
            ->where('ticker', 'not like', '%' . self::FRACTIONAL_SUFFIX);
    }

    public static function isFractionalTicker(string $ticker): bool
    {
        return str_ends_with(strtoupper($ticker), self::FRACTIONAL_SUFFIX);
    }

    public function isTradable(): bool
    {
        return $this->is_active
            && (float) $this->current_price > 0
            && ! self::isFractionalTicker($this->ticker);
    }
}
