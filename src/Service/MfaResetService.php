<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class MfaResetService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function reset(User $user): void
    {
        $user->setSecretKey('');
        $this->entityManager->flush();
    }
}
