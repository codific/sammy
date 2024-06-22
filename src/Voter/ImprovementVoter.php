<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\AssessmentStream;
use App\Entity\Improvement;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Improvement>
 */
class ImprovementVoter extends Voter
{

    public const ATTRIBUTES = [
        'FINISH_IMPROVEMENT_STREAM' => 'FINISH_IMPROVEMENT_STREAM',
    ];

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Improvement;
    }


    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !in_array(Role::USER->string(), $user->getRoles(), true)) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['FINISH_IMPROVEMENT_STREAM'] => $this->improvementActive($subject) && in_array(Role::IMPROVER->string(), $user->getRoles(), true),
            default => false
        };
    }

    private function improvementActive(Improvement $improvement): bool
    {
        $assessmentStream = $improvement->getAssessmentStream();

        return $this->inProgress($assessmentStream) || $this->completed($assessmentStream);
    }

    private function inProgress(AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::IN_IMPROVEMENT && $assessmentStream->getCurrentStage() instanceof Improvement;
    }

    private function completed(AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::COMPLETE && $assessmentStream->getCurrentStage() instanceof Improvement;
    }
}
