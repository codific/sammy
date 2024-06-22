<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\Role;
use App\Repository\AssessmentRepository;
use App\Repository\GroupProjectRepository;
use App\Repository\ProjectRepository;
use App\Service\AssessmentService;
use App\Service\ProjectService;
use App\Tests\_support\AbstractKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use function PHPUnit\Framework\any;

class ProjectServiceTest extends AbstractKernelTestCase
{
    private ProjectService $projectService;

    public function setUp(): void
    {
        parent::setUp();
        $this->projectService = self::getContainer()->get(ProjectService::class);
        $session = new Session(new MockFileSessionStorage());
        $session->start();
        $requestMock = $this->createMock(RequestStack::class);
        $requestMock->expects(any())->method('getSession')->willReturn($session);
        $this->projectService = $this->getMockBuilder(ProjectService::class)->setConstructorArgs(
            [
                self::getContainer()->get(EntityManagerInterface::class),
                self::getContainer()->get(ProjectRepository::class),
                $requestMock,
                self::getContainer()->get(GroupProjectRepository::class),
                self::getContainer()->get(AssessmentService::class),
                self::getContainer()->get(AssessmentRepository::class),
            ]
        )->onlyMethods([])->getMock();
    }

    /**
     * @dataProvider getCurrentProjectProvider
     */
    public function testGetCurrentProject(User $user, ?Project $setCurrentProject, ?Project $expectedProject)
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($expectedProject);
        $this->entityManager->flush();
        $this->projectService->setCurrentProject($setCurrentProject);
        $project = $this->projectService->getCurrentProject();
        self::assertEquals($project, $expectedProject);
    }

    public function getCurrentProjectProvider(): array
    {
        ($project = new Project());

        return [
            "Positive 1 - User within an org and project he has access to" => [
                ($user = new User())
                    ->addUserGroupUser(
                        (new GroupUser())->setUser($user)
                            ->setGroup(($group = new Group())->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group)))
                    ),
                $project,
                $project,
            ],
            "Positive 2 - Manager within an org and project he has no direct access to" => [
                ($user = new User())->setRoles([Role::MANAGER->string()])
                    ->addUserGroupUser(
                        (new GroupUser())->setUser($user)
                            ->setGroup(
                                ($group = new Group())->addGroupGroupProject(
                                    (new GroupProject())->setProject((new Project()))->setGroup($group)
                                )
                            )
                    ),
                $project,
                $project,
            ],
        ];
    }

    public function testGetAvailableProjectsForUser()
    {
        //user1 belongs to group1 and has access to project1 and 2
        //user2 belongs to group2 and has access to project2 and 3
        ($project1 = new Project());
        ($project2 = new Project());
        ($project3 = new Project());
        ($user1 = new User());
        ($user2 = new User());
        ($group1 = new Group)->addGroupGroupUser(($groupUser1 = new GroupUser())->setUser($user1)->setGroup($group1))->addGroupGroupUser(
            ($groupUser2 = new GroupUser())->setUser($user2)->setGroup($group1)
        );
        ($group2 = new Group)->addGroupGroupUser(($groupUser3 = new GroupUser())->setUser($user2)->setGroup($group2));
        $user1->addUserGroupUser($groupUser1)->addUserGroupUser($groupUser2);
        $user2->addUserGroupUser($groupUser3);
        $group1->addGroupGroupProject((new GroupProject())->setGroup($group1)->setProject($project1))->addGroupGroupProject(
            (new GroupProject())->setGroup($group1)->setProject($project2)
        );
        $group2->addGroupGroupProject((new GroupProject())->setGroup($group2)->setProject($project2))->addGroupGroupProject(
            (new GroupProject())->setGroup($group2)->setProject($project3)
        );
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($group1);
        $this->entityManager->persist($group2);
        $this->entityManager->flush();
        $projects = $this->projectService->getAvailableProjectsForUser($user1);
        self::assertContains($project1, $projects);
        self::assertContains($project2, $projects);
        $projects = $this->projectService->getAvailableProjectsForUser($user2);
        self::assertContains($project2, $projects);
        self::assertContains($project3, $projects);
    }

    public function testGetGroupProjectDataIndexedByGroup()
    {
        //group1 has project1 and 2
        //group2 has project2 and 3
        ($project1 = new Project());
        ($project2 = new Project());
        ($project3 = new Project());
        ($group1 = new Group)->addGroupGroupProject((new GroupProject())->setGroup($group1)->setProject($project1))->addGroupGroupProject(
            (new GroupProject())->setGroup($group1)->setProject($project2)
        );
        ($group2 = new Group)->addGroupGroupProject((new GroupProject())->setGroup($group2)->setProject($project2))->addGroupGroupProject(
            (new GroupProject())->setGroup($group2)->setProject($project3)
        );
        $this->entityManager->persist($group1);
        $this->entityManager->persist($group2);
        $this->entityManager->flush();
        $projects = $this->projectService->getProjectsIndexedByGroup();
        self::assertArrayHasKey($group1->getId(), $projects);
        self::assertArrayHasKey($group2->getId(), $projects);
        self::assertContains($project1->getId(), $projects[$group1->getId()]);
        self::assertContains($project2->getId(), $projects[$group1->getId()]);
        self::assertContains($project2->getId(), $projects[$group2->getId()]);
        self::assertContains($project3->getId(), $projects[$group2->getId()]);
    }

    public function testGetGroupProjectDataIndexedByProject()
    {
        //group1 has project1 and 2
        //group2 has project2 and 3
        ($project1 = new Project());
        ($project2 = new Project());
        ($project3 = new Project());
        ($group1 = new Group)->addGroupGroupProject((new GroupProject())->setGroup($group1)->setProject($project1))->addGroupGroupProject(
            (new GroupProject())->setGroup($group1)->setProject($project2)
        );
        ($group2 = new Group)->addGroupGroupProject((new GroupProject())->setGroup($group2)->setProject($project2))->addGroupGroupProject(
            (new GroupProject())->setGroup($group2)->setProject($project3)
        );
        $this->entityManager->persist($group1);
        $this->entityManager->persist($group2);
        $this->entityManager->flush();
        $groups = $this->projectService->getGroupsIndexedByProject();
        self::assertArrayHasKey($project1->getId(), $groups);
        self::assertArrayHasKey($project2->getId(), $groups);
        self::assertArrayHasKey($project3->getId(), $groups);
        self::assertContains($group1->getId(), $groups[$project1->getId()]);
        self::assertContains($group1->getId(), $groups[$project2->getId()]);
        self::assertContains($group2->getId(), $groups[$project2->getId()]);
        self::assertContains($group2->getId(), $groups[$project3->getId()]);
    }

    /**
     * @dataProvider modifyGroupProjectsProvider
     */
    public function testModifyGroupProjects(Group $group, array $projects)
    {
        foreach ($projects as $project) {
            $this->entityManager->persist($project);
        }
        $this->entityManager->persist($group);
        $this->entityManager->flush();
        $projectIds = [];
        foreach ($projects as $project) {
            $projectIds[] = $project->getId();
        }
        $this->projectService->modifyGroupProjects($group, $projectIds);
        /** @var Group $group */
        $group = $this->entityManager->getRepository(Group::class)->findOneBy(['id' => $group->getId()]);

        $groupProjectIds = [];
        foreach ($group->getGroupProjects() as $groupProject) {
            $groupProjectIds[] = $groupProject->getProject()->getId();
            if (!in_array($groupProject->getProject()->getId(), $projectIds, true)) {
                self::assertNotNull($groupProject->getDeletedAt());
            } else {
                self::assertNull($groupProject->getDeletedAt());
            }
        }
        foreach ($projectIds as $id) {
            self::assertContains($id, $groupProjectIds);
        }
    }

    public function modifyGroupProjectsProvider(): array
    {
        ($project1 = new Project());
        ($project2 = new Project());
        ($project3 = new Project());

        return [
            "Test - 1 Modify to add all 3 projects to group with 2 projects, expecting all 3" => [
                ($group = new Group)->addGroupGroupProject(
                    (new GroupProject())->setGroup($group)->setProject($project1)
                )->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject($project2)),
                [$project1, $project2, $project3],
            ],
            "Test - 2 Modify to add 1 project to group with 2 projects, expecting only the last one" => [
                ($group = new Group)->addGroupGroupProject(
                    (new GroupProject())->setGroup($group)->setProject($project1)
                )->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject($project2)),
                [$project3],
            ],
            "Test - 3 Add an empty array, expecting no group projects" => [
                ($group = new Group)->addGroupGroupProject(
                    (new GroupProject())->setGroup($group)->setProject($project1)
                )->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject($project2)),
                [],
            ],
        ];
    }

    /**
     * @dataProvider modifyProjectGroupsProvider
     */
    public function testModifyProjectGroups(Project $project, array $groups)
    {
        foreach ($groups as $group) {
            $this->entityManager->persist($group);
        }
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        $groupIds = [];
        foreach ($groups as $group) {
            $groupIds[] = $group->getId();
        }
        $this->projectService->modifyProjectGroups($project, $groupIds);
        /** @var Project $project */
        $project = $this->entityManager->getRepository(Project::class)->findOneBy(['id' => $project->getId()]);

        $groupProjectIds = [];
        $groupProjects = $this->entityManager->getRepository(GroupProject::class)->findBy(['project' => $project]);
        foreach ($groupProjects as $groupProject) {
            $groupProjectIds[] = $groupProject->getGroup()->getId();
            if (!in_array($groupProject->getGroup()->getId(), $groupIds, true)) {
                self::assertNotNull($groupProject->getDeletedAt());
            } else {
                self::assertNull($groupProject->getDeletedAt());
            }
        }
        foreach ($groupIds as $id) {
            self::assertContains($id, $groupProjectIds);
        }
        if (sizeof($groups) === 0) {
            self::assertEmpty($groupProjects);
        }
    }

    public function modifyProjectGroupsProvider(): array
    {
        ($group1 = new Group());
        ($group2 = new Group());
        ($group3 = new Group());

        return [
            "Modify to add 3 group to a project without groups" => [
                ($project = new Project()),
                [$group1, $group2, $group3],
            ],
            "Modify to add 1 project to group with 2 projects, expecting only the last one" => [
                ($project = new Project()),
                [$group3],
            ],
            "Add an empty array, expecting no group projects" => [
                ($project = new Project()),
                [],
            ],
        ];
    }

    /**
     * @dataProvider testUnarchiveProjectProvider
     * @testdox $_dataName
     */
    public function testUnarchiveProject(Project $project)
    {
        $groupProjectRepository = $this->entityManager->getRepository(GroupProject::class);
        $groupProject = (new GroupProject())->setProject($project)->setDeletedAt(new \DateTime());
        $this->entityManager->persist($groupProject);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        self::assertNotNull($project->getDeletedAt());
        self::assertNotNull($project->getAssessment()->getDeletedAt());
        self::assertNotNull($groupProjectRepository->findOneBy(['project' => $project])->getDeletedAt());
        foreach ($project->getAssessment()->getAssessmentAssessmentStreams() as $assessmentStream) {
            self::assertNotNull($assessmentStream->getDeletedAt());
        }

        $this->projectService->unarchiveProject($project);

        self::assertNull($project->getDeletedAt());
        self::assertNull($project->getAssessment()->getDeletedAt());
        self::assertNull($groupProjectRepository->findOneBy(['project' => $project])->getDeletedAt());
        foreach ($project->getAssessment()->getAssessmentAssessmentStreams() as $assessmentStream) {
            self::assertNull($assessmentStream->getDeletedAt());
        }
    }

    private function testUnarchiveProjectProvider(): array
    {
        $project1 = (new Project());
        $assessment1 = (new Assessment());

        return [
            "Test 1 - Test that the project, the assessment, the groupproject and the assessmentstreams are restored after unarchive" => [
                $project1
                    ->setDeletedAt(new \DateTime())
                    ->setAssessment(
                        $assessment1->setProject($project1)
                            ->setDeletedAt(new \DateTime())
                            ->addAssessmentAssessmentStream(
                                (new AssessmentStream())
                                    ->setDeletedAt(new \DateTime())
                                    ->setAssessment($assessment1)
                            )
                            ->addAssessmentAssessmentStream(
                                (new AssessmentStream())
                                    ->setDeletedAt(new \DateTime())
                                    ->setAssessment($assessment1)
                            )
                    ), // project
            ],
        ];
    }
}