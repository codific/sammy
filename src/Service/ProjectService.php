<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\AssessmentRepository;
use App\Repository\GroupProjectRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RequestStack;

class ProjectService
{
    public const CURRENT_PROJECT_SESSION_KEY = 'current.project';
    private const PROJECT_IS_TEMPLATE_SESSION_KEY = 'current.project.isTemplate';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly RequestStack $requestStack,
        private readonly GroupProjectRepository $groupProjectRepository,
        private readonly AssessmentService $assessmentService
    ) {
    }

    /**
     * I've dropped access control here as that should be checked when setting the project
     * No need to set the project here either.
     *
     * @throws NonUniqueResultException
     */
    public function getCurrentProject(): ?Project
    {
        /** @var ?Project $project */
        $project = $this->requestStack->getSession()->get(self::CURRENT_PROJECT_SESSION_KEY);
        $project = ($project !== null) ? $this->entityManager->find(Project::class, $project->getId()) : null;

        if ($project === null || $project->isDeleted()) {
            $this->setCurrentProject(null);

            return null;
        }

        return $this->entityManager->getReference(Project::class, $project->getId());
    }

    /**
     * I strongly believe we shouldn't do any access control checks here. All access control should happen on the caller side.
     */
    public function setCurrentProject(?Project $targetProject): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::CURRENT_PROJECT_SESSION_KEY, $targetProject);
        $session->set(self::PROJECT_IS_TEMPLATE_SESSION_KEY, $targetProject?->isTemplate() ?? false);
    }

    public function createProject(
        string $name,
        ?string $description,
        array $groupIds,
        int $metamodelId,
        float $validationThreshold = 0,
        bool $template = false
    ): Project {
        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
        $project->setValidationThreshold($validationThreshold);
        $project->setTemplate($template);
        $project->setMetamodel($this->entityManager->getReference(Metamodel::class, $metamodelId));
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        $this->addGroupsToProject($project, $groupIds);

        $newAssessment = $this->assessmentService->createAssessment($project);
        $project->setAssessment($newAssessment);

        return $project;
    }

    public function addGroupsToProject(Project $project, array $groups): void
    {
        foreach ($groups as $groupId) {
            $groupProject = new GroupProject();
            $groupProject->setProject($project);
            $group = $this->entityManager->getReference(Group::class, $groupId);
            $groupProject->setGroup($group);
            $group->addGroupGroupProject($groupProject);
            $this->entityManager->persist($groupProject);
        }
        $this->entityManager->flush();
    }

    public function addGroupToProjects(Group $group, array $projects): void
    {
        foreach ($projects as $projectId) {
            $groupProject = new GroupProject();
            $project = $this->entityManager->getReference(Project::class, $projectId);
            $groupProject->setProject($project);
            $groupProject->setGroup($group);
            $group->addGroupGroupProject($groupProject);
            $this->entityManager->persist($groupProject);
        }
        $this->entityManager->flush();
    }

    public function getAvailableProjectsForUser(User $user): array
    {
        return $this->projectRepository->findByUser($user);
    }

    public function getProjectsIndexedByGroup(): array
    {
        $result = [];
        $groupProjects = $this->groupProjectRepository->findAllOptimized();
        foreach ($groupProjects as $groupProject) {
            $group = $groupProject->getGroup();
            $result[$group->getId()][] = $groupProject->getProject()->getId();
        }

        return $result;
    }

    public function getGroupsIndexedByProject(): array
    {
        $result = [];
        $groupProjects = $this->groupProjectRepository->findAllOptimized();
        foreach ($groupProjects as $groupProject) {
            $project = $groupProject->getProject();
            $result[$project->getId()][] = $groupProject->getGroup()->getId();
        }

        return $result;
    }

    public function modifyGroupProjects(Group $group, array $newProjectIds): void
    {
        $newProjectIds = array_combine($newProjectIds, $newProjectIds);

        $groupProjects = $this->groupProjectRepository->findAllByGroup($group);
        foreach ($groupProjects as $groupProject) {
            if (!in_array($groupProject->getProject()->getId(), $newProjectIds, true)) {
                $this->groupProjectRepository->trash($groupProject);
            } else {
                unset($newProjectIds[$groupProject->getProject()->getId()]);
            }
        }

        $this->addGroupToProjects($group, $newProjectIds);
    }

    public function modifyProjectGroups(Project $project, array $newGroupIds): void
    {
        $newGroupIds = array_combine($newGroupIds, $newGroupIds);

        $groupProjects = $this->groupProjectRepository->findAllByProject($project);
        foreach ($groupProjects as $groupProject) {
            if (!in_array($groupProject->getGroup()->getId(), $newGroupIds, true)) {
                $this->groupProjectRepository->trash($groupProject);
            } else {
                unset($newGroupIds[$groupProject->getGroup()->getId()]);
            }
        }
        $this->addGroupsToProject($project, $newGroupIds);
    }

    public function deleteTemplateProjectLinks(Project $project): void
    {
        $linkedProjects = $this->projectRepository->findBy(['templateProject' => $project]);

        foreach ($linkedProjects as $linkedProject) {
            $linkedProject->setTemplateProject(null);
        }
    }

    public function unarchiveProject(Project $project): void
    {
        $projectRepository = $this->entityManager->getRepository(Project::class);
        $projectRepository->deepRestore($project);
    }
}
