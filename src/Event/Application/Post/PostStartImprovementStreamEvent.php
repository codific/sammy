<?php

declare(strict_types=1);

namespace App\Event\Application\Post;

use App\Entity\AssessmentStream;
use App\Enum\ImprovementStatus;
use Symfony\Contracts\EventDispatcher\Event;

class PostStartImprovementStreamEvent extends Event
{
    public function __construct(protected ?AssessmentStream $assessmentStream, protected ?ImprovementStatus $oldImprovementStatus = null)
    {
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }

    public function getOldImprovementStatus(): ?ImprovementStatus
    {
        return $this->oldImprovementStatus;
    }
}
