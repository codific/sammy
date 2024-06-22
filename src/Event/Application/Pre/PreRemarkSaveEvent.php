<?php

declare(strict_types=1);

namespace App\Event\Application\Pre;

use App\Entity\AssessmentStream;
use App\Entity\Remark;
use Symfony\Contracts\EventDispatcher\Event;

class PreRemarkSaveEvent extends Event
{
    public function __construct(protected ?AssessmentStream $assessmentStream, protected ?Remark $remark = null)
    {
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }

    public function getRemark(): ?Remark
    {
        return $this->remark;
    }
}
