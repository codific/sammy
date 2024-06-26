<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Service\MailingService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class ChangedPasswordListener
{
    public function __construct(private readonly MailingService $mailingService, private $user = null)
    {
    }

    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        $passwordChanged = $event->getEntityChangeSet()['password'] ?? false;
        if ($passwordChanged) {
            $this->user = $user;
        }
    }

    /** @phpstan-ignore-next-line TODO: FIX THIS */
    public function postUpdate(User $user, LifecycleEventArgs $args): void
    {
        if ($this->user === $user) {
            $this->user = null;
            $this->mailingService->add(\App\Enum\MailTemplateType::CHANGED_PASSWORD, $user);
        }
    }
}
