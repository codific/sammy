<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, AssessmentStream>
 */
class AssessmentStreamVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
        private readonly AssessmentStreamVoterHelper $assessmentStreamVoterHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public const ATTRIBUTES = [
        'ANSWER_STREAM' => 'ANSWER_STREAM',
        'SUBMIT_STREAM' => 'SUBMIT_STREAM',
        'RETRACT_STREAM' => 'RETRACT_STREAM',
        'UNDO_VALIDATION' => 'UNDO_VALIDATION',
        'VALIDATE_STREAM' => 'VALIDATE_STREAM',
        'EDIT_VALIDATION' => 'EDIT_VALIDATION',
        'REJECT_STREAM' => 'REJECT_STREAM',
        'START_IMPROVE_STREAM' => 'START_IMPROVE_STREAM',
        'ASSESSMENT_STREAM_ACCESS' => 'ASSESSMENT_STREAM_ACCESS',
        'SAVE_REMARK' => 'SAVE_REMARK',
        'ASSIGN_STREAM_USER' => 'ASSIGN_STREAM_USER',
    ];

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof AssessmentStream;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !in_array(Role::USER->string(), $user->getRoles(), true)) {
            return false;
        }

        if ($this->hasAssessmentStreamAccess($subject) === false) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['ASSESSMENT_STREAM_ACCESS'] => true,
            self::ATTRIBUTES['ANSWER_STREAM'], self::ATTRIBUTES['SUBMIT_STREAM'] => $this->canAnswerStream($user, $subject),
            self::ATTRIBUTES['RETRACT_STREAM'] => $this->canRetractStream($user, $subject),
            self::ATTRIBUTES['UNDO_VALIDATION'] => $this->canUndoValidation($user, $subject),
            self::ATTRIBUTES['VALIDATE_STREAM'], self::ATTRIBUTES['REJECT_STREAM'] => $this->canValidate($user, $subject),
            self::ATTRIBUTES['EDIT_VALIDATION'] => $this->canEditValidation($user, $subject),
            self::ATTRIBUTES['START_IMPROVE_STREAM'] => $this->canImprove($user, $subject),
            self::ATTRIBUTES['SAVE_REMARK'] => $this->canSaveRemark(),
            self::ATTRIBUTES['ASSIGN_STREAM_USER'] => $this->canAssignUserToStream($subject),
            default => false
        };
    }

    private function submittable(AssessmentStream $assessmentStream): bool
    {
        return ($assessmentStream->getStatus() === \App\Enum\AssessmentStatus::NEW || $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::IN_EVALUATION)
            && ($assessmentStream->getCurrentStage() === null || $assessmentStream->getCurrentStage() instanceof Evaluation);
    }

    private function canAnswerStream(User $user, AssessmentStream $assessmentStream): bool
    {
        return ($this->submittable($assessmentStream) && in_array(Role::EVALUATOR->string(), $user->getRoles(), true))
            || $this->auditorException($assessmentStream, $user);
    }

    private function submittedBy(UserInterface $user, AssessmentStream $assessmentStream): bool
    {
        return $this->submitted($assessmentStream) && $user === $assessmentStream->getSubmittedBy();
    }

    private function submitted(AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::IN_VALIDATION && $assessmentStream->getCurrentStage() instanceof Validation;
    }

    private function validated(AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::VALIDATED && $assessmentStream->getCurrentStage() instanceof Improvement;
    }

    private function inProgress(AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::IN_IMPROVEMENT && $assessmentStream->getCurrentStage() instanceof Improvement;
    }

    private function canEditValidation(User $user, AssessmentStream $assessmentStream): bool
    {
        return $this->validated($assessmentStream) && $assessmentStream->getLastValidationStage()->getSubmittedBy() === $user;
    }

    private function canSaveRemark(): bool
    {
        return $this->security->isGranted(Role::EVALUATOR->string())
            || $this->security->isGranted(Role::VALIDATOR->string())
            || $this->security->isGranted(Role::IMPROVER->string())
            || $this->security->isGranted(Role::MANAGER->string());
    }

    private function canAssignUserToStream(AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() !== \App\Enum\AssessmentStatus::COMPLETE && $assessmentStream->getStatus() !== \App\Enum\AssessmentStatus::ARCHIVED;
    }

    private function hasAssessmentStreamAccess(AssessmentStream $assessmentStream): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTES['PROJECT_ACCESS'], $assessmentStream->getAssessment()->getProject());
    }

    private function canRetractStream(User $user, AssessmentStream $assessmentStream): bool
    {
        return $this->assessmentStreamVoterHelper->canStreamBeRetracted($user, $assessmentStream);
    }

    private function canUndoValidation(User $user, AssessmentStream $assessmentStream): bool
    {
        return $this->assessmentStreamVoterHelper->canUndoValidation($user, $assessmentStream);
    }

    private function canValidate(User $user, AssessmentStream $assessmentStream): bool
    {
        return $this->submitted($assessmentStream) && $this->hasRole($user, Role::VALIDATOR) && $this->noSelfValidation($user, $assessmentStream);
    }

    private function canImprove(User $user, AssessmentStream $assessmentStream): bool
    {
        return ($this->validated($assessmentStream) || $this->inProgress($assessmentStream)) && in_array(Role::IMPROVER->string(), $user->getRoles(), true);
    }

    private function auditorException(AssessmentStream $assessmentStream, User $user): bool
    {
        return in_array(Role::AUDITOR->string(), $user->getRoles(), true)
            && $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::IN_VALIDATION
            && $assessmentStream->getCurrentStage() instanceof Validation;
    }

    /** Evaluators can't self validate, except if they are Manager or Auditor */
    private function noSelfValidation(User $user, AssessmentStream $assessmentStream): bool
    {
        return !$this->submittedBy($user, $assessmentStream) || ($this->hasRole($user, Role::MANAGER) || $this->hasRole($user, Role::AUDITOR));
    }

    private function hasRole(User $user, Role $role): bool
    {
        return in_array($role->string(), $user->getRoles(), true);
    }
}
