<?php

declare(strict_types=1);

namespace App\Event\Application\Post;

use App\Entity\AssessmentStream;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class PostAddAssignmentEvent extends Event
{
    public function __construct(protected ?AssessmentStream $assessmentStream, protected User $user)
    {
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
