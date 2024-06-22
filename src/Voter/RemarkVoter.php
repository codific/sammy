<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\Remark;
use App\Entity\User;
use App\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Remark>
 */
class RemarkVoter extends Voter
{
    public const ATTRIBUTES = [
        'DELETE_REMARK' => 'DELETE_REMARK',
    ];

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Remark;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !in_array(Role::USER->string(), $user->getRoles(), true)) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['DELETE_REMARK'] => $this->canDeleteRemark($user, $subject),
            default => false
        };
    }

    private function canDeleteRemark(User $user, Remark $remark): bool
    {
        $assessmentStream = $remark->getStage()->getAssessmentStream();

        return ($remark->getUser() === $user) && $assessmentStream->getStatus() !== \App\Enum\AssessmentStatus::ARCHIVED;
    }
}
