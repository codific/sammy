<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Abstraction\PasswordResetInterface;
use App\Utils\RandomStringGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordService
{
    private string $passwordResetHashValid = '+8 hours';
    private string $welcomeResetHashValid = '+8 hours';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RandomStringGenerator $generator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfigurationService $configurationService
    ) {
        $this->passwordResetHashValid = (string) $this->configurationService->get('passwordResetHashValid', $this->passwordResetHashValid);
        $this->welcomeResetHashValid = (string) $this->configurationService->get('welcomeResetHashValid', $this->welcomeResetHashValid);
    }

    public function reset(PasswordResetInterface $entity, bool $isWelcomeMail = false): bool
    {
        $resetHashValid = $this->passwordResetHashValid;
        if ($isWelcomeMail) {
            $resetHashValid = $this->welcomeResetHashValid;
        }

        try {
            if ($entity->getPasswordResetHash() === '' || (new \DateTime()) > $entity->getPasswordResetHashExpiration()) {
                $entity->setPasswordResetHash($this->generator->base32(16));
            }
            $entity->setPasswordResetHashExpiration(new \DateTime($resetHashValid));
            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace()[0]);

            return false;
        }
    }
}
