<?php

declare(strict_types=1);

namespace App\ViewStructures\Timeline;

class EventIcon
{
    public function __construct(public readonly string $icon, public readonly string $color)
    {
    }
}
