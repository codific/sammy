<?php

declare(strict_types=1);

namespace App\Security\Listener;

use App\Security\Badge\NotCompromisedPasswordBadge;
use App\Service\BreachedPasswordService;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotCompromisedPasswordListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly BreachedPasswordService $breachedPasswordService,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(NotCompromisedPasswordBadge::class)) {
            return;
        }
        /** @var NotCompromisedPasswordBadge $badge */
        $badge = $passport->getBadge(NotCompromisedPasswordBadge::class);
        if ($badge->isResolved()) {
            return;
        }
        if ($this->breachedPasswordService->check($badge->getPassword())) {
            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('warning', $this->translator->trans('general.breached_password', [], 'security'));
        }
        $badge->markResolved();
    }

    #[ArrayShape([CheckPassportEvent::class => 'array'])]
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => ['checkPassport', -100]];
    }
}
