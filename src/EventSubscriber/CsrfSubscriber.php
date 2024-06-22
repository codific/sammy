<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Utils\Constants;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfSubscriber implements EventSubscriberInterface
{
    private const WHITELISTED_ROUTES = [' _preview', '_wdt', '_profiler', 'qr', 'ef', 'app_documentation_efconnect', 'css', 'images', 'js', 'api_'];
    private Security $security;
    private CsrfTokenManagerInterface $csrfTokenManager;

    /**
     * CsrfSubscriber constructor.
     */
    public function __construct(Security $security, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->security = $security;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest'],
            ],
        ];
    }

    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if ($request->isXmlHttpRequest() && !$this->isWhitelistedRoute($request->attributes->get('_route'))) {
            if (!$request->headers->has(Constants::CSRF_HEADER) && !$request->query->has('csrf')) {
                $event->setResponse(new JsonResponse(['status' => 'error', 'message' => 'Missing csrf token!'], JsonResponse::HTTP_BAD_REQUEST));

                return;
            }

            $user = $this->security->getUser();
            if ($user instanceof User && !$this->csrfTokenManager->isTokenValid(new CsrfToken((string) $user->getId(), $request->query->get('csrf')))
                && !$this->csrfTokenManager->isTokenValid(new CsrfToken((string) $user->getId(), $request->headers->get(Constants::CSRF_HEADER)))
            ) {
                $event->setResponse(new JsonResponse(['status' => 'error', 'message' => 'Invalid csrf token!'], JsonResponse::HTTP_BAD_REQUEST));
            }
        }
    }

    private function isWhitelistedRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }
        foreach (self::WHITELISTED_ROUTES as $whitelistedRoute) {
            if (str_starts_with($routeName, $whitelistedRoute)) {
                return true;
            }
        }

        return false;
    }
}
