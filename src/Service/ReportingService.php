<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\MetamodelRepository;
use App\Repository\ProjectRepository;
use App\Utils\Constants;

class ReportingService
{
    public function __construct(
        private readonly ScoreService $scoreService,
        private readonly ProjectRepository $projectRepository,
        private readonly MetamodelRepository $metamodelRepository,
    ) {
    }

    public function getScoreForUserProjects(\DateTime $dateTime, User $user, Project $currentProject = null, Metamodel $metamodel = null): array
    {
        $metamodel ??= $currentProject?->getMetamodel() ?? $this->metamodelRepository->find(Constants::SAMM_ID);
        $userProjects = $this->projectRepository->findByUser($user);

        if ($currentProject !== null) {
            $userProjects += [$currentProject];
        }

        $userProjects = array_filter($userProjects, fn (Project $project) => $project->getMetamodel()->getId() === $metamodel->getId());

        return $this->scoreService->getProjectScores($dateTime, true, ...$userProjects);
    }

    public function getPercentageOfTargetScopeForProjects(\DateTime $dateTime, User $user, Project $currentProject = null, Metamodel $metamodel = null, array $projectIds = [], bool $validated = true): array
    {
        $metamodel ??= $currentProject?->getMetamodel() ?? $this->metamodelRepository->find(Constants::SAMM_ID);
        $userProjects = $this->projectRepository->findByUser($user);
        if ($currentProject !== null) {
            $userProjects += [$currentProject];
            if (!in_array((string) $currentProject->getId(), $projectIds, true)) {
                $projectIds[] = (string) $currentProject->getId();
            }
        }

        $userProjects = array_unique($userProjects);
        $userProjects = array_filter(
            $userProjects,
            fn (Project $project) => $project->getMetamodel()->getId() === $metamodel->getId() && $project->getTemplateProject() !== null
                && (sizeof($projectIds) > 0 ? in_array((string) $project->getId(), $projectIds, true) : true)
        );

        $targetProjects = array_filter(array_unique(array_map(fn (Project $proj) => $proj->getTemplateProject(), $userProjects)), fn (?Project $proj) => $proj !== null);

        $projectScores = $this->scoreService->getProjectScoresByQuestion($dateTime, $validated, ...array_merge($userProjects, $targetProjects));

        return $this->calculateProjectsPercentageToTarget($userProjects, $projectScores);
    }

    private function calculateProjectsPercentageToTarget(array $userProjects, array $projectScores): array
    {
        $getCurrentAndTargetScores = function (Project $project) use ($projectScores) {
            $targetProj = $project->getTemplateProject();

            return [$projectScores[$project->getId()]['score'], $targetProj === null ? null : $projectScores[$targetProj->getId()]['score']];
        };

        $getGaps = function ($scores, $targetScores) {
            $gaps = [];
            foreach ($scores as $questionId => $questionScore) {
                $gaps[] = $targetScores[$questionId] - $questionScore;
            }

            return $gaps;
        };

        $calculatePercentage = function (Project $project) use ($getGaps, $getCurrentAndTargetScores) {
            [$scores, $targetScores] = $getCurrentAndTargetScores($project);

            if ($targetScores === null) {
                return null;
            }

            $targetSum = (float) array_sum($targetScores);
            $gapSum = (float) array_sum(array_filter($getGaps($scores, $targetScores), fn ($gap) => $gap > 0));

            return $targetSum > 0 ? (1 - ($gapSum / $targetSum)) : 1;
        };

        return array_map(
            fn (Project $proj) => ['name' => $proj->getName(), 'pct' => $calculatePercentage($proj)],
            $userProjects
        );
    }
}
