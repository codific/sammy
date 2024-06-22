<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Assessment;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\EntityManagerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjectRepositoryTest extends AbstractKernelTestCase
{
    private ProjectRepository $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRepository = $this->getContainer()->get(ProjectRepository::class);
    }

    /**
     * @dataProvider findOneByProjectOptimizedProvider
     */
    public function testFindOneByProjectOptimized(array $mixedDbObjects, Project $project, User $user, ?Project $expectedProject)
    {
        foreach ($mixedDbObjects as $object) {
            $this->entityManager->persist($object);
        }
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        if ($expectedProject !== null) {
            $this->entityManager->persist($expectedProject);
        }
        $this->entityManager->flush();
        $projectFromDb = $this->projectRepository->findOneByProjectAndUserOptimized($project, $user);
        self::assertEquals($expectedProject, $projectFromDb);
    }

    public function findOneByProjectOptimizedProvider(): \Generator
    {
        yield "Positive 1" => [
            "projects" => [
                ($project = new Project())->setAssessment(($assessment = new Assessment())->setProject($project)),
                ($group = new Group())->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group))
                    ->addGroupGroupUser((new GroupUser())->setUser(($user = new User()))->setGroup($group)),
            ],
            "project" => $project,
            "user" => $user,
            "expectedProject" => $project,
        ];
        yield "Negative 2" => [
            "projects" => [
                ($project = new Project())->setAssessment(($assessment = new Assessment())->setProject($project)),
                ($group = new Group())->addGroupGroupUser((new GroupUser())->setUser(($user = new User()))->setGroup($group)),
            ],
            "project" => $project,
            "user" => $user,
            "expectedProject" => null,
        ];
        yield "Negative 3" => [
            "projects" => [
                ($project = new Project())->setAssessment(($assessment = new Assessment())->setProject($project)),
                ($group = new Group())->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group)),
                ($user = new User()),
            ],
            "project" => $project,
            "user" => $user,
            "expectedProject" => null,
        ];

    }

    /**
     * @dataProvider findOptimizedProvider
     */
    public function testFindOptimized(array $projects, ?bool $template, array $expectedProjects)
    {
        foreach ($projects as $project) {
            $this->entityManager->persist($project);
        }
        $this->entityManager->flush();
        $projectsFromDb = $this->projectRepository->findOptimized($template);
        self::assertSameSize($projectsFromDb, $expectedProjects);
        foreach ($projectsFromDb as $key => $project) {
            self::assertContains($project, $expectedProjects);
        }
    }

    /**
     * @dataProvider findOptimizedProvider
     */
    public function testFindAllIndexedById(array $projects, ?bool $template, array $expectedProjects)
    {
        foreach ($projects as $project) {
            $this->entityManager->persist($project);
        }
        $this->entityManager->flush();
        $projectsFromDb = $this->projectRepository->findOptimized($template, "project.id");
        self::assertSameSize($projectsFromDb, $expectedProjects);
        foreach ($projectsFromDb as $key => $project) {
            self::assertEquals($key, $project->getId());
            self::assertContains($project, $expectedProjects);
        }
    }

    public function findOptimizedProvider(): array
    {
        return [
            "Positive 1: 1 org, 2 projects" => [
                "projects" => [
                    ($project = new Project())->setAssessment(($assessment = new Assessment())->setProject($project)),
                    ($project2 = new Project())->setAssessment(($assessment2 = new Assessment())->setProject($project2)),
                ],
                "template" => false,
                "expectedProject" => [$project, $project2],
            ],
            "Positive 2: get template project 1 org, 2 projects, 1 template " => [
                "projects" => [
                    ($project = new Project())->setAssessment(($assessment = new Assessment())->setProject($project)),
                    ($project2 = new Project())->setAssessment(($assessment2 = new Assessment())->setProject($project2))->setTemplate(true),
                ],
                "template" => true,
                "expectedProject" => [$project2],
            ],
            "Positive 3: org has only a template project, expected empty set" => [
                "projects" => [
                    ($project2 = new Project())->setAssessment(($assessment2 = new Assessment())->setProject($project2))->setTemplate(true),
                ],
                "template" => false,
                "expectedProject" => [],
            ],
            "Positive 4: org has only a template project, but we request all types" => [
                "projects" => [
                    ($project2 = new Project())->setAssessment(($assessment2 = new Assessment())->setProject($project2))->setTemplate(true),
                ],
                "template" => null,
                "expectedProject" => [$project2],
            ],
        ];
    }

    /**
     * @dataProvider findByUserProvider
     */
    public function testFindByUser(array $databaseObjects, User $user, array $expectedProjects)
    {
        foreach ($databaseObjects as $object) {
            $this->entityManager->persist($object);
        }
        $this->entityManager->flush();
        $projectsFromDb = $this->projectRepository->findByUser($user);
        self::assertSameSize($projectsFromDb, $expectedProjects);
        foreach ($projectsFromDb as $project) {
            self::assertContains($project, $expectedProjects);
        }
    }

    public function findByUserProvider(): array
    {
        return [
            "Positive 1: 1 user, 2 groups, 2 projects" => [
                "projects" => [
                    ($user = new User())
                        ->addUserGroupUser(
                            (new GroupUser())->setUser($user)->setGroup(
                                ($group = new Group())
                                    ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project = new Project())))
                            )
                        ),
                    $user->addUserGroupUser(
                        (new GroupUser())->setUser($user)->setGroup(
                            ($group2 = new Group())
                                ->addGroupGroupProject((new GroupProject())->setGroup($group2)->setProject(($project2 = new Project())))
                        )
                    ),
                ],
                "user" => $user,
                "expectedProject" => [$project, $project2],
            ],
            "Positive 2: 1 user, 1 group, 2 projects" => [
                "projects" => [
                    ($user = new User())
                        ->addUserGroupUser(
                            (new GroupUser())->setUser($user)->setGroup(
                                ($group = new Group())
                                    ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project = new Project())))
                                    ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project2 = new Project())))
                            )
                        ),
                ],
                "user" => $user,
                "expectedProject" => [$project, $project2],
            ],
        ];
    }

    /**
     * @dataProvider findByGroupsProvider
     */
    public function testFindByGroups(array $allGroups, array $findByGroups, array $expectedProjects)
    {
        foreach ($allGroups as $group) {
            $this->entityManager->persist($group);
        }
        $this->entityManager->flush();
        $projectsFromDb = $this->projectRepository->findByGroups($findByGroups);
        self::assertSameSize($projectsFromDb, $expectedProjects);
        foreach ($projectsFromDb as $project) {
            self::assertContains($project, $expectedProjects);
        }
    }

    public function findByGroupsProvider(): array
    {
        return [
            "Positive 1: 1 group, 2 projects, find projects of the 1 group" => [
                "projects" => [
                    ($group = new Group())
                        ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project = new Project())))
                        ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project2 = new Project()))),
                ],
                "groups" => [$group],
                "expectedProject" => [$project, $project2],
            ],
            "Positive 2: 2 groups, one project each, find projects of both groups" => [
                "projects" => [
                    ($group = new Group())
                        ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project = new Project()))),
                    ($group2 = new Group())
                        ->addGroupGroupProject((new GroupProject())->setGroup($group2)->setProject(($project2 = new Project()))),
                ],
                "groups" => [$group, $group2],
                "expectedProject" => [$project, $project2],
            ],
            "Positive 3: 3 groups, one project each, find the projects of 2 out of these 3 groups" => [
                "projects" => [
                    ($group = new Group())
                        ->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject(($project = new Project()))),
                    ($group2 = new Group())
                        ->addGroupGroupProject((new GroupProject())->setGroup($group2)->setProject(($project2 = new Project()))),
                    ($group3 = new Group())
                        ->addGroupGroupProject((new GroupProject())->setGroup($group3)->setProject(($project3 = new Project()))),
                ],
                "groups" => [$group, $group2],
                "expectedProject" => [$project, $project2],
            ],
        ];
    }

}