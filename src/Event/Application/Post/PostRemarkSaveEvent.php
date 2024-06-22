<?php

declare(strict_types=1);

namespace App\Event\Application\Post;

use App\Entity\AssessmentStream;
use App\Entity\Remark;
use Symfony\Contracts\EventDispatcher\Event;

class PostRemarkSaveEvent extends Event
{
    public function __construct(protected ?AssessmentStream $assessmentStream, protected Remark $remark)
    {
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }

    public function getRemark(): Remark
    {
        return $this->remark;
    }
}
