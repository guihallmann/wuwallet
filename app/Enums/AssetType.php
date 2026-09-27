<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetType: string
{
    case ON = 'on';
    case PN = 'pn';
    case UNIT = 'unit';
}
