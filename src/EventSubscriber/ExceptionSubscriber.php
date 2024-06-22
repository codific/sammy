<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * ErrorSubscriber constructor.
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => [['onRequest']]];
    }

    public function onRequest(ExceptionEvent $event)
    {
        if (!$event->isMainRequest() || $event->getRequest()->isXmlHttpRequest()) {
            return;
        }
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if ($user === null) {
            return;
        }
        if ($event->getThrowable() instanceof HttpException && $event->getThrowable()->getStatusCode() === Response::HTTP_FORBIDDEN) {
            $this->handle403($event);
        }
    }

    private function handle403(ExceptionEvent $event)
    {
        $request = $event->getRequest();
        if (str_starts_with($request->attributes->get('_route'), 'app_') && $this->security->isGranted('ROLE_ADMIN')) {
            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('warning', $this->translator->trans('admin.general.imitate_error'));
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('admin_index_index')));
        }
        if (str_starts_with($request->attributes->get('_route'), 'admin_') && $this->security->isGranted('ROLE_USER')) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_index')));
        }

        if ($request->attributes->get('_route') === '2fa_login' || $request->attributes->get('_route') === '2fa_login_check') {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('admin_index_index')));
        }
        if ($request->attributes->get('_route') === '2fa_front_login' || $request->attributes->get('_route') === '2fa_front_login_check') {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_index')));
        }
    }
}
