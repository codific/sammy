<?php

declare(strict_types=1);

namespace App\Event\Application\Pre;

use App\Entity\AssessmentStream;
use Symfony\Contracts\EventDispatcher\Event;

class PreAddNewStageEvent extends Event
{
    public function __construct(protected ?AssessmentStream $assessmentStream)
    {
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }
}
