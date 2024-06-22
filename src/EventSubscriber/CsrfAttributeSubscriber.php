<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Attribute\CsrfToken as CsrfTokenAttribute;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

readonly class CsrfAttributeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TokenStorageInterface $tokenStorage,
        private ExpressionLanguage $expressionLanguage = new ExpressionLanguage()
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!\is_array($attributes = $event->getAttributes()[CsrfTokenAttribute::class] ?? null)) {
            return;
        }
        $request = $event->getRequest();
        $arguments = $event->getNamedArguments();
        /** @var CsrfTokenAttribute $attribute */
        $attribute = reset($attributes);
        if ($attribute->methods !== [] && !in_array($request->getMethod(), $attribute->methods, true)) {
            return;
        }
        $id = $this->getId($attribute, $request, $arguments);
        $csrfToken = $request->get($attribute->token);
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($id, $csrfToken))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, $attribute->getMessage());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 20]];
    }

    private function getId(CsrfTokenAttribute $csrfToken, Request $request, array $arguments): string
    {
        $token = $this->tokenStorage->getToken();
        $variables = [
            'user' => $token->getUser(),
            'request' => $request,
        ];
        $diff = array_intersect(array_keys($variables), array_keys($arguments));
        if ($diff !== []) {
            foreach ($diff as $key => $variableName) {
                if ($variables[$variableName] === $arguments[$variableName]) {
                    unset($diff[$key]);
                }
            }
        }

        // controller variables should also be accessible
        $variables = array_merge($arguments, $variables);

        try {
            $id = (string) $this->expressionLanguage->evaluate($csrfToken->id, $variables);
        } catch (\Throwable $t) {
            $id = $csrfToken->id;
        }

        return $id;
    }
}
