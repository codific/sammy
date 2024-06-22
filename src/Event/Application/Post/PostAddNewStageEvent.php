<?php

declare(strict_types=1);

namespace App\Event\Application\Post;

use App\Entity\AssessmentStream;
use Symfony\Contracts\EventDispatcher\Event;

class PostAddNewStageEvent extends Event
{
    public function __construct(protected ?AssessmentStream $assessmentStream)
    {
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }
}
