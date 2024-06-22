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

namespace App\Event\Admin\Create;

// #BlockStart number=34 id=_19_0_3_40d01a2_1635864059122_214388_5671_#_0

use App\Entity\User;
use App\Enum\MailTemplateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedEvent extends Event
{
    /**
     * UserCreatedEvent constructor.
     */
    public function __construct(protected ?Request $request, protected User $user, protected MailTemplateType $mailTemplate = MailTemplateType::USER_WELCOME)
    {
    }

    /**
     * Get the http request.
     */
    public function getRequest(): ?Request
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

    public function getMailTemplate(): MailTemplateType
    {
        return $this->mailTemplate;
    }
}

// #BlockEnd number=34
