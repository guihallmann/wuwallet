<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case BUY = 'buy';
    case SELL = 'sell';
}
