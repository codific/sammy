<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Group;
use App\Entity\GroupProject;
use App\Repository\GroupProjectRepository;
use App\Repository\GroupRepository;
use App\Repository\ProjectRepository;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupRepository $groupRepository,
        private readonly TranslatorInterface $translator,
        private readonly ScoreService $scoreService,
        private readonly GroupProjectRepository $groupProjectRepository,
        private readonly ProjectRepository $projectRepository,

    ) {
    }

    public function createGroup(string $name, ?Group $parent): Group
    {
        $group = new Group();
        $group->setName($name);
        $group->setParent($parent);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $group;
    }

    public function getGroupsData(): array
    {
        $result = [];
        $groups = $this->groupRepository->findAll();
        foreach ($groups as $group) {
            $result[$group->getId()] = $group->getName();
        }

        return $result;
    }

    public function orderGroupByParent(): array
    {
        $result = [];
        $groups = $this->groupRepository->findAll();
        $result["root"] = "Root";
        $result["global"] = [];
        foreach ($groups as $group) {
            $unitParent = $group->getParent();
            if ($unitParent === null) {
                $result["global"][] = $group;
            } else {
                $result[$group->getParent()->getId()][] = $group;
            }
        }

        return $result;
    }

    public function getPossibleParentNamesAndIds(Group $selectedGroup, ?string $search = null): array
    {
        if ($search === null) {
            $search = "";
        }
        $groups = $this->groupRepository->findAllIndexedById($search);

        $result = [];
        $result[] = ["text" => $this->translator->trans("application.group.no_parent", [], "application"), "value" => 0];
        foreach ($groups as $groupId => $group) {
            if ($selectedGroup !== $group && !$this->doesGroupContainsInParents($group, $selectedGroup)) {
                $result[] = ["text" => $group->getName(), "value" => $groupId];
            }
        }

        return $result;
    }

    public function doesGroupContainsInParents(Group $group, Group $parent): bool
    {
        $currentGroup = $group;
        while ($currentGroup->getParent() !== null) {
            if ($currentGroup->getParent() === $parent) {
                return true;
            }
            $currentGroup = $currentGroup->getParent();
        }

        return false;
    }

    public function getSammPropagatedLeafGroupScores(): array
    {
        $groups = $this->groupRepository->findAllIndexedById();
        $projects = $this->projectRepository->findByGroups($groups);
        $groupProjects = $this->groupProjectRepository->findAllOptimized();
        $projectScores = $this->scoreService->getProjectScores(new \DateTime('now'), true, ...$projects);
        $groupsByParent = $this->orderGroupByParent();
        $childGroupScores = $this->getLeafGroupsSammScores($groupProjects, $groupsByParent, $projectScores);

        return $this->propagateScoreToParents($childGroupScores, $groups);
    }

    private function getLeafGroupsSammScores(array $groupProjects, array $groupsByParent, array $projectScores): array
    {
        $sammScores = [];
        /** @var GroupProject $groupProject */
        foreach ($groupProjects as $groupProject) {
            $group = $groupProject->getGroup();
            $project = $groupProject->getProject();
            $isLeafNode = !array_key_exists($group->getId(), $groupsByParent);
            if ($project->getMetamodel()->getId() === Constants::SAMM_ID && $isLeafNode) {
                $sammScores[$group->getId()][] = $projectScores[$project->getId()];
            }
        }

        $result = [];
        foreach ($sammScores as $groupId => $scores) {
            $totalScore = 0;
            foreach ($scores as $score) {
                $totalScore += $score['arithmeticMean'];
            }
            $result[$groupId] = number_format($totalScore / sizeof($scores), 2);
        }

        return $result;
    }

    private function propagateScoreToParents(array $groupScores, array $groups): array
    {
        foreach ($groupScores as $groupId => $score) {
            /** @var Group $currentGroup */
            $currentGroup = $groups[$groupId];
            while ($currentGroup->getParent() !== null) {
                $groupScores[$currentGroup->getParent()->getId()][] = $score;
                $currentGroup = $currentGroup->getParent();
            }
        }

        foreach ($groupScores as $groupId => $scores) {
            if (is_array($scores)) {
                $groupScore = 0;
                foreach ($scores as $score) {
                    $groupScore += $score;
                }
                $groupScores[$groupId] = number_format($groupScore / sizeof($scores), 2);
            }
        }

        return $groupScores;
    }
}
