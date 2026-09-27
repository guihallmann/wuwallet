<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasUuids;

    protected $table = 'transactions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'asset_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'transaction_date',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_type' => TransactionType::class,
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'transaction_date' => 'datetime',
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
