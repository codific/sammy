<?php

declare(strict_types=1);

namespace App\Voter\Auth;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User>
 */
class SwitchUser extends Voter
{
    public const ATTRIBUTES = [
        'CAN_SWITCH_USER' => 'CAN_SWITCH_USER',
    ];

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User
            || !$subject instanceof User
            || !$user->isAdmin()
            || $subject->isAdmin()) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['CAN_SWITCH_USER'] => $this->security->isGranted('ROLE_ADMIN'),
            default => false
        };
    }
}
