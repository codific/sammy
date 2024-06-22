<?php

declare(strict_types=1);

namespace App\Enum;

enum ModelEntitySyncEnum: int
{
    case UNTOUCHED = 0;
    case ADDED = 1;
    case MODIFIED = 2;
}
