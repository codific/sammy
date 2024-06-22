<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\User;
use App\Enum\Role;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User>
 */
class UserVoter extends Voter
{
    public const ATTRIBUTES = [
        'USER_CHANGE_PASSWORD' => 'USER_CHANGE_PASSWORD',
        'USER_MODIFY' => 'USER_MODIFY',
        'USER_ORGANIZATION' => 'USER_ORGANIZATION',
        'USER_FILL_ORGANIZATION_INFO' => 'USER_FILL_ORGANIZATION_INFO',
        'USER_EDIT' => 'USER_EDIT',
        'USER_DELETE_ORGANIZATION' => 'USER_DELETE_ORGANIZATION',
    ];

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['USER_CHANGE_PASSWORD'] => $this->isUserInternal($subject),
            self::ATTRIBUTES['USER_MODIFY'] => $this->isUserInternal($subject),
            self::ATTRIBUTES['USER_ORGANIZATION'] => true,
            self::ATTRIBUTES['USER_FILL_ORGANIZATION_INFO'] => $this->isUserAllowedToFillInfo($subject),
            self::ATTRIBUTES['USER_EDIT'] => $this->isUserAllowedToEdit($subject, $user),
            self::ATTRIBUTES['USER_DELETE_ORGANIZATION'] => $this->isUserAllowedToDeleteOrganization($subject),
            default => false
        };
    }

    private function isUserInternal(User $user): bool
    {
        return true;
    }

    private function isUserAllowedToFillInfo(User $user): bool
    {
        return $this->security->isGranted(Role::MANAGER->string());
    }

    public function isUserAllowedToDeleteOrganization(User $user): bool
    {
        return $this->security->isGranted(Role::MANAGER->string());
    }


    private function isUserAllowedToEdit(User $user, User $userWhoEdits): bool
    {
        return in_array(Role::MANAGER->string(), $userWhoEdits->getRoles(), true);
    }
}
