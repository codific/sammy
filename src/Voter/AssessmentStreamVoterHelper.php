<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\AssessmentStream;
use App\Entity\Improvement;
use App\Entity\User;
use App\Enum\AssessmentStatus;
use App\Enum\ValidationStatus;
use App\Service\AssessmentAnswersService;

class AssessmentStreamVoterHelper
{

    public function __construct(private readonly AssessmentAnswersService $assessmentAnswersService)
    {
    }

    public function canStreamBeRetracted(User $user, AssessmentStream $assessmentStream): bool
    {
        return $user === $assessmentStream->getSubmittedBy() && (
                $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::IN_VALIDATION ||
                (
                    $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::VALIDATED &&
                    $assessmentStream->getLastValidationStage()->getStatus() === \App\Enum\ValidationStatus::AUTO_ACCEPTED &&
                    strlen($assessmentStream->getLastValidationStage()?->getComment() ?? '') === 0 &&
                    sizeof(
                        $this->assessmentAnswersService->getLatestAssessmentStreamAnswers(
                            $assessmentStream,
                            \App\Enum\AssessmentAnswerType::DESIRED
                        )
                    ) === 0 &&
                    sizeof($assessmentStream->getCurrentStage()->getStageRemarks()) === 0 &&
                    $assessmentStream->getCurrentStage() instanceof Improvement &&
                    strlen($assessmentStream->getCurrentStage()->getPlan() ?? '') === 0
                )
            );
    }

    public function canUndoValidation(User $user, AssessmentStream $assessmentStream): bool
    {
        return $user === $assessmentStream->getSubmittedBy() &&
            $assessmentStream->getStatus() === AssessmentStatus::VALIDATED &&
            $assessmentStream->getLastValidationStage()->getStatus() === ValidationStatus::ACCEPTED &&
            $assessmentStream->getCurrentStage() instanceof Improvement &&
            sizeof(
                $this->assessmentAnswersService->getLatestAssessmentStreamAnswers(
                    $assessmentStream,
                    \App\Enum\AssessmentAnswerType::DESIRED
                )
            ) === 0 &&
            strlen($assessmentStream->getCurrentStage()->getPlan() ?? '') === 0 &&
            $assessmentStream->getLastImprovementStage()->getCreatedAt() >= new \DateTime('-24 hours');
    }
}
