<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class SessionListener
{
    public const TIMESTAMP = '_session.timestamp';
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($event->isMainRequest()) {
            $request = $event->getRequest();
            $session = $request->getSession();
            $timestamp = $session->get(self::TIMESTAMP);
            if ($timestamp === null) {
                return;
            }
            $timeDifference = time() - (int) $timestamp;
            $timeout = (int) $this->parameterBag->get('session.timeout');
            if ($timeDifference > $timeout) {
                $session->invalidate();
            }
        }
    }
}
