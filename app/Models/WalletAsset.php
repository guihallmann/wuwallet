<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletAsset extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'asset_id',
        'target_percentage',
        'current_quantity',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'target_percentage' => 'decimal:2',
        'current_quantity' => 'integer',
    ];

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
