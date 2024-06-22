<?php

declare(strict_types=1);

namespace App\Event\Application;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class ToaAcceptedEvent extends Event
{
    /**
     * ToaAcceptedEvent constructor.
     */
    public function __construct(protected Request $request, protected User $user)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
