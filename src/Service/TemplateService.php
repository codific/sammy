<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AssessmentStream;
use App\Repository\RemarkRepository;

class TemplateService
{
    public function __construct(
        private readonly RemarkRepository $remarkRepository
    ) {
    }

    public function getTemplateRemarksByAssessmentStream(AssessmentStream $assessmentStream): array
    {
        $stream = $assessmentStream->getStream();
        $template = $assessmentStream->getAssessment()->getProject()->getTemplateProject();
        if ($template === null) {
            return [];
        }
        $targetStream = $template->getAssessment()->getAssessmentAssessmentStreams()->filter(fn (AssessmentStream $templateStream) => $templateStream->getStream() === $stream);

        return $this->remarkRepository->findByAssessmentStreams($targetStream->toArray());
    }
}
