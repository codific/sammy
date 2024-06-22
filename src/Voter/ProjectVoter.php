<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Project>
 */
class ProjectVoter extends Voter
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public const ATTRIBUTES = [
        'PROJECT_ACCESS' => 'PROJECT_ACCESS',
        'PROJECT_EDIT' => 'PROJECT_EDIT',
    ];

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::ATTRIBUTES['PROJECT_ACCESS'] => $this->projectAccess($user, $subject),
            self::ATTRIBUTES['PROJECT_EDIT'] => $this->projectEdit($user, $subject),
            default => false
        };
    }

    private function projectAccess(User $user, Project $project): bool
    {
        $userProjects = $this->em->getRepository(Project::class)->findByUser($user);

        return ($project->isTemplate() === false && in_array($project, $userProjects, true)) ||
            in_array(Role::MANAGER->string(), $user->getRoles(), true);
    }

    private function projectEdit(User $user, Project $project): bool
    {
        return in_array(Role::MANAGER->string(), $user->getRoles(), true) && $this->projectAccess($user, $project);
    }
}
