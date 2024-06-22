<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\MetamodelRepository;
use App\Repository\ProjectRepository;
use App\Service\ReportingService;
use App\Service\ScoreService;
use App\Tests\EntityManagerTestCase;

class ReportingServiceTest extends EntityManagerTestCase
{
    /**
     * @dataProvider testGetScoresForUserProjectsDataProvider
     */
    public function testGetScoresForUserProjects(
        array $entitiesToPersist,
        ?Project $currentProject,
        ?Metamodel $metamodel,
        ?Metamodel $returnedMetamodelFromDb,
        array $returnedProjectsFromDb,
        array $expectedProjectsToPassToScoreService
    ): void {
        foreach ($entitiesToPersist as $entityToPersist) {
            $this->entityManager->persist($entityToPersist);
            $this->entityManager->flush();
        }

        $datetime = new \DateTime('now');
        $scoreServiceMock = $this->createMock(ScoreService::class);
        $scoreServiceMock->expects($this->once())->method("getProjectScores")->with($datetime, true, ...$expectedProjectsToPassToScoreService);

        $projectRepositoryMock = $this->createMock(ProjectRepository::class);
        $projectRepositoryMock->method("findByUser")->willReturn($returnedProjectsFromDb);

        $metamodelRepositoryMock = $this->createMock(MetamodelRepository::class);
        $metamodelRepositoryMock->method("find")->willReturn($returnedMetamodelFromDb);

        $reportingService = new ReportingService(
            $scoreServiceMock,
            $projectRepositoryMock,
            $metamodelRepositoryMock,
        );
        $reportingService->getScoreForUserProjects($datetime, new User(), $currentProject, $metamodel);
    }

    private function testGetScoresForUserProjectsDataProvider(): array
    {
        $templateProject = (new Project())->setName("Template Name");
        $metamodel = new Metamodel();
        $metamodel2 = new Metamodel();
        $project = (new Project())->setName("Project Name")->setTemplateProject($templateProject)->setMetamodel($metamodel);
        $project2 = (new Project())->setName("Project Name 2")->setMetamodel($metamodel);
        $project3 = (new Project())->setName("Project Name 2");
        $project4 = (new Project())->setName("Project Name 2")->setMetamodel($metamodel2);

        return [
            "Positive 1, passing project and metamodel, expect 2 projects passed to ScoreService" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $project4],
                $project,
                $metamodel,
                null,
                [$project2, $project, $project4],
                [$project2, $project],
            ],
            "Positive 2, no passing project and passing metamodel, expect one project passed to ScoreService" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $project4],
                null,
                $metamodel,
                null,
                [$project2, $project, $project4],
                [$project2],
            ],
            "Positive 3, no passing project and no passing metamodel, expect two project passed to ScoreService" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $project4],
                null,
                null,
                $metamodel,
                [$project2, $project, $project4],
                [$project2, $project],
            ],
            "Positive 3, passing project and no passing metamodel, expect two project passed to ScoreService" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $project4],
                $project,
                null,
                $metamodel,
                [$project2, $project, $project4],
                [$project2, $project],
            ],
            "Negative 1, passing project from different metamodel and no passing metamodel, expect no project passed to ScoreService" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $project4],
                $project4,
                null,
                $metamodel,
                [],
                [],
            ],
        ];
    }

    /**
     * @dataProvider testGetPercentageOfTargetScopeForProjectsDataProvider
     */
    public function testGetPercentageOfTargetScopeForProjects(
        array $entitiesToPersist,
        Project $currentProject,
        array $returnedProjectsFromDb,
        Metamodel $returnedMetamodelFromDb,
        array $projectToKeep,
        array $expectedResult
    ): void {
        foreach ($entitiesToPersist as $entityToPersist) {
            $this->entityManager->persist($entityToPersist);
            $this->entityManager->flush();
        }

        $projectIdsToKeep = [];
        $scoreServiceMock = $this->createMock(ScoreService::class);
        $scoreServiceResult = [];
        foreach ($returnedProjectsFromDb as $index => $project) {
            if (in_array($project, $projectToKeep, true)) {
                $projectIdsToKeep[] = (string)$project->getId();
            }
            $score = [];
            $gaps = [];
            $target = 0.5;
            $values = [0, 0.25, 0.5, 1];
            for ($i = 0; $i < 90; $i++) {
                $val = $values[array_rand($values)];
                $score[] = $val;
                if ($val <= $target) {
                    $gaps[] = $target - $val;
                }
            }
            $scoreServiceResult[$project->getId()] = [
                "score" => $score,
                "projectName" => $project->getName(),
            ];

            if ($project->getTemplateProject() !== null) {
                $scoreServiceResult[$project->getTemplateProject()->getId()] = [
                    "score" => array_fill(0, 90, $target),
                    "projectName" => $project->getTemplateProject()->getName(),
                ];
            }
            if (in_array($project, array_merge($projectToKeep, [$currentProject]), true)) {
                $expectedResult[$index]['pct'] = 1 - (array_sum($gaps) / (90 * $target));
            }
        }

        $scoreServiceMock->method("getProjectScoresByQuestion")->willReturn($scoreServiceResult);

        $projectRepositoryMock = $this->createMock(ProjectRepository::class);
        $projectRepositoryMock->method("findByUser")->willReturn($returnedProjectsFromDb);

        $metamodelRepositoryMock = $this->createMock(MetamodelRepository::class);
        $metamodelRepositoryMock->method("find")->willReturn($returnedMetamodelFromDb);

        $reportingService = new ReportingService(
            $scoreServiceMock,
            $projectRepositoryMock,
            $metamodelRepositoryMock
        );

        $actualResult = $reportingService->getPercentageOfTargetScopeForProjects(new \DateTime('now'), new User(), $currentProject, null, $projectIdsToKeep);

        self::assertEquals($expectedResult, $actualResult);
    }

    private function testGetPercentageOfTargetScopeForProjectsDataProvider(): array
    {
        $templateProject = (new Project())->setName("Template Name");
        $metamodel = new Metamodel();
        $project = (new Project())->setName("Project Name")->setTemplateProject($templateProject)->setMetamodel($metamodel);
        $project2 = (new Project())->setName("Project Name 2")->setMetamodel($metamodel);
        $project3 = (new Project())->setName("Project Name 3")->setTemplateProject($templateProject)->setMetamodel($metamodel);
        $metamodel2 = new Metamodel();
        $project4 = (new Project())->setName("Project Name 4")->setMetamodel($metamodel2);
        $project5 = (new Project())->setName("Project Name 4")->setTemplateProject($templateProject)->setMetamodel($metamodel2);

        return [
            "Positive 1, expect returned current project with percentage" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $metamodel2, $project4, $project5],
                $project,
                [$project],
                $metamodel,
                [],
                [
                    [
                        "name" => "Project Name",
                        "pct" => 0.0,
                    ],
                ],
            ],
            "Positive 2, expect only current project with percentage" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $metamodel2, $project4, $project5],
                $project,
                [$project, $project3, $project4, $project5],
                $metamodel,
                [],
                [
                    [
                        "name" => "Project Name",
                        "pct" => 0.0,
                    ],
                ],
            ],
            "Positive 3, expect 1 project with percentage, because of passed projectIds to filter on" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $metamodel2, $project4, $project5],
                $project,
                [$project, $project3, $project4, $project5],
                $metamodel,
                [$project],
                [
                    [
                        "name" => "Project Name",
                        "pct" => 0.0,
                    ],
                ],
            ],
            "Positive 4, expect 2 projects with percentage, because of passed projectIds to filter on" => [
                [$templateProject, $metamodel, $project, $project2, $project3, $metamodel2, $project4, $project5],
                $project,
                [$project, $project3, $project4, $project5],
                $metamodel,
                [$project, $project3],
                [
                    [
                        "name" => "Project Name",
                        "pct" => 0.0,
                    ],
                    [
                        "name" => "Project Name 3",
                        "pct" => 1.2987012987012987,
                    ],
                ],
            ],
        ];
    }

}