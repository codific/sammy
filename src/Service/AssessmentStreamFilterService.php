<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Repository\AssessmentStreamRepository;

class AssessmentStreamFilterService
{
    public function __construct(
        private readonly AssessmentStreamRepository $assessmentStreamRepository,
    ) {
    }

    public static function getActiveStreams(Assessment $assessment, bool $sorted = false): array
    {
        $assessmentStreams = $assessment->getAssessmentAssessmentStreams()->filter(
            fn (AssessmentStream $assessmentStream) => ($assessmentStream->getStatus() !== \App\Enum\AssessmentStatus::ARCHIVED)
        )->toArray();

        if ($sorted) {
            $assessmentStreams = AssessmentService::sortAssessmentStreams($assessmentStreams);
        }

        return $assessmentStreams;
    }

    public function getAssessmentStreamsByDate(?Assessment $assessment, \DateTime $dateTime): array
    {
        if ($assessment === null) {
            return [];
        }

        return $this->assessmentStreamRepository->findLatestByAssessmentAndDate($assessment, $dateTime);
    }

    public function getValidatedAssessmentStreamsByDate(?Assessment $assessment, \DateTime $dateTime): array
    {
        if ($assessment === null) {
            return [];
        }

        return $this->assessmentStreamRepository->findLatestValidatedByAssessmentAndDate($assessment, $dateTime);
    }
}
