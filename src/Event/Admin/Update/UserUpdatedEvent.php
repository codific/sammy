<?php
/**
 * This is automatically generated file using the Codific Prototizer
 * PHP version 8.
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Event\Admin\Update;

// #BlockStart number=35 id=_19_0_3_40d01a2_1635864059122_214388_5671_#_0

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class UserUpdatedEvent extends Event
{
    /**
     * UserUpdatedEvent constructor.
     *
     * @return void
     */
    public function __construct(protected Request $request, protected User $user)
    {
    }

    /**
     * Get the http request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get User.
     */
    public function getUser(): User
    {
        return $this->user;
    }
}

// #BlockEnd number=35
