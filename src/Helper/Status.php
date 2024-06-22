<?php

declare(strict_types=1);

namespace App\Helper;

readonly class Status
{
    public function __construct(
        public bool $ok,
        public ?string $message = null
    ) {
    }
}

