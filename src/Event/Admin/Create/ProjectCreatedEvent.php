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

// #BlockStart number=20 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_0

use App\Entity\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class ProjectCreatedEvent extends Event
{
    /**
     * ProjectCreatedEvent constructor.
     */
    public function __construct(protected ?Request $request, protected Project $project)
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
     * Get Project.
     */
    public function getProject(): Project
    {
        return $this->project;
    }
}

// #BlockEnd number=20
