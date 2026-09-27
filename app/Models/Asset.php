<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'ticker',
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

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
