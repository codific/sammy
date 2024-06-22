<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\Group;
use App\Entity\User;
use App\Enum\Role;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Group>
 */
class GroupVoter extends Voter
{
    public const ATTRIBUTES = [
        'GROUP_ACCESS' => 'GROUP_ACCESS',
        'GROUP_EDIT' => 'GROUP_EDIT',
    ];

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Group;
    }

    /**
     * @param mixed $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['GROUP_ACCESS'] => true,
            self::ATTRIBUTES['GROUP_EDIT'] => $this->groupEdit($user, $subject),
            default => false
        };
    }

    private function groupEdit(User $user, Group $group): bool
    {
        return in_array(Role::MANAGER->string(), $user->getRoles(), true);
    }
}
