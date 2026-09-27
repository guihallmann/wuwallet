<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'objective_text',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WalletAsset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(WalletAsset::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Sums the current market value of every asset held in this wallet.
     * Requires `assets.asset` to be eager-loaded to avoid N+1 queries.
     */
    public function totalValue(): float
    {
        return (float) $this->assets->sum(
            fn (WalletAsset $walletAsset): float => $walletAsset->current_quantity * (float) $walletAsset->asset->current_price
        );
    }
}
