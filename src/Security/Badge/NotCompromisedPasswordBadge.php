<?php

declare(strict_types=1);

namespace App\Security\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class NotCompromisedPasswordBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(private readonly string $password)
    {
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @internal
     */
    public function markResolved(): void
    {
        $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
