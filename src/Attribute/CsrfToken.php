<?php

declare(strict_types=1);

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class CsrfToken
{
    public function __construct(public string $token = 'token', public string $id = 'token', public array $methods = [])
    {
    }

    public function getMessage(): string
    {
        return 'Invalid CSRF token.';
    }
}
