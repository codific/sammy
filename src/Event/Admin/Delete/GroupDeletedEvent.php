<?php

declare(strict_types=1);

namespace App\Event\Admin\Delete;


use App\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class GroupDeletedEvent extends Event
{
    public function __construct(protected Request $request, protected Group $group)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }
}