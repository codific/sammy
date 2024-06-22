<?php

namespace App\Tests\functional;

use App\Entity\Assessment;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\ProjectRepository;
use App\Service\AssessmentService;
use App\Service\MetamodelService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\GroupBuilder;
use App\Tests\builders\ProjectBuilder;
use App\Tests\builders\UserBuilder;
use App\Utils\Constants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProjectControllerTest extends AbstractWebTestCase
{
    /**
     * @group security
     * @group asvs
     * @dataProvider testEditGroupsEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that editing groups at '/edit_groups/{id}' $_dataName
     */
    public function testEditGroupsEndpointDOA(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project ProjectControllerTest")->setMetamodel($metamodel);
        self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $project->getAssessment();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($testUser, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request(
            Request::METHOD_POST,
            $this->urlGenerator->generate(
                'app_project_edit_groups',
                [
                    'id' => $project->getId(),
                ]
            )
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    private function testEditGroupsEndpointDOAProvider(): array
    {
        // users without group
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        return [
            "Positive 1 - is allowed for a user, who is in the org and has manager role" => [
                $userInOrganizationAndManager, // user
                Response::HTTP_OK,
            ],
            "Negative 1 - is not allowed for a user, who is in the org, but he is not a manager" => [
                $userInOrganizationAndRegularUser, // user
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    /**
     * @group security
     * @group asvs
     * @dataProvider testAjaxModifyEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that modifying ajax's description at '/ajaxmodify/{id}' $_dataName
     */
    public function testAjaxModifyEndpointDOA(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $container = static::getContainer();


        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project")->setMetamodel($metamodel);
        $container->get(AssessmentService::class)->createAssessment($project);
        $project->getAssessment();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }


        $this->client->loginUser($testUser, "boardworks");

        $payload = [
            'name' => 'name',
            'value' => 'newProjectName-'.$project->getId(),
        ];

        $this->client->request(
            Request::METHOD_POST,
            $this->urlGenerator->generate(
                'app_project_ajaxmodify',
                [
                    'id' => $project->getId(),
                ]
            ),
            $payload
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    private function testAjaxModifyEndpointDOAProvider(): array
    {
        // users without group
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();


        return [
            "Positive 1 - is allowed for a user, who is in the org and has manager role" => [
                $userInOrganizationAndManager, // user
                Response::HTTP_OK,
            ],
            "Negative 1 - is not allowed for a user, who is in the org, but he is not a manager" => [
                $userInOrganizationAndRegularUser, // user
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }


    /**
     * @group security
     * @group asvs
     * @dataProvider testOverviewPartialEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that full overview at '/overviewPartial/{id}' $_dataName
     */
    public function testOverviewPartialEndpointDOA(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project")->setMetamodel($metamodel);
        $group = (new GroupBuilder())->build();
        $group->addGroupGroupProject(
            (new GroupProject())
                ->setGroup($group)
                ->setProject($project)
        );

        self::getContainer()->get(AssessmentService::class)->createAssessment($project);

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($testUser, "boardworks");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_reporting_overview',
                [
                    'id' => $project->getId(),
                ]
            )
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    private function testOverviewPartialEndpointDOAProvider(): array
    {
        $userInOrganizationAndInGroupAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndManagerInAnotherGroup = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndManagerNotInAnyGroups = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndInGroupAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();

        $userNotInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        // users without group
        $userInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();

        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        return [
            "Positive 1 - is allowed for a user, who is in the org and has a manager role and is in the same group as the project" => [
                $userInOrganizationAndInGroupAndManager,
                Response::HTTP_OK,
            ],
            "Positive 2 - is allowed for a user, who is in the org and has a manager role, but he is not in the current group project" => [
                $userInOrganizationAndManagerInAnotherGroup,
                Response::HTTP_OK,
            ],
            // (bugfix, see commit: {84b106eb} for more info)
            "Positive 3 - is allowed for a user, who is in the org and has a manager role, but he is not a part of any groups" => [
                $userInOrganizationAndManagerNotInAnyGroups,
                Response::HTTP_OK,
            ],
            "Negative 1 - is not allowed for a regular user, who is in the same org and in the same group" => [
                $userInOrganizationAndInGroupAndRegularUser,
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    /**
     * @group security
     * @group asvs
     * @dataProvider testOverviewFullEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that full overview at '/overview/{id}' $_dataName
     */
    public function testOverviewFullEndpointDOA(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project")->setMetamodel($metamodel);
        $group = (new GroupBuilder())->build();
        $group->addGroupGroupProject(
            (new GroupProject())
                ->setGroup($group)
                ->setProject($project)
        );

        self::getContainer()->get(AssessmentService::class)->createAssessment($project);

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($testUser, "boardworks");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_reporting_overview',
                [
                    'id' => $project->getId(),
                ]
            )
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    private function testOverviewFullEndpointDOAProvider(): array
    {
        $userInOrganizationAndInGroupAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndManagerInAnotherGroup = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndManagerNotInAnyGroups = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndInGroupAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        // users without group
        $userInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        return [
            "Positive 1 - is allowed for a user, who is in the org and has a manager role and is in the same group as the project" => [
                $userInOrganizationAndInGroupAndManager,
                Response::HTTP_OK,
            ],
            "Positive 2 - is allowed for a user, who is in the org and has a manager role, but he is not in the current group project" => [
                $userInOrganizationAndManagerInAnotherGroup,
                Response::HTTP_OK,
            ],
            // (bugfix, see commit: {84b106eb} for more info)
            "Positive 3 - is allowed for a user, who is in the org and has a manager role, but he is not a part of any groups" => [
                $userInOrganizationAndManagerNotInAnyGroups,
                Response::HTTP_OK,
            ],
            "Negative 1 - is not allowed for a regular user, who is in the same org and in the same group" => [
                $userInOrganizationAndInGroupAndRegularUser,
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    /**
     * @group security
     * @group asvs
     * @dataProvider testDeleteEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that deletion on '/{id}' $_dataName
     */
    public function testDeleteEndpointDOA(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $container = static::getContainer();
        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project")->setMetamodel($metamodel);
        $container->get(AssessmentService::class)->createAssessment($project);
        $project->getAssessment();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($testUser, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request(
            Request::METHOD_DELETE,
            $this->urlGenerator->generate(
                'app_project_delete',
                [
                    'id' => $project->getId(),
                ]
            )
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    private function testDeleteEndpointDOAProvider(): array
    {
        // users without group
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        return [
            "Positive 1 - is allowed for a user, who is in the org and has manager role" => [
                $userInOrganizationAndManager, // user
                Response::HTTP_OK,
            ],
            "Negative 1 - is not allowed for a user, who is in the org, but he is not a manager" => [
                $userInOrganizationAndRegularUser, // user
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    public function testAddSingleProject(): void
    {
        //Arrange
        $userActor = (new UserBuilder($this->entityManager))->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        //Login
        $this->client->loginUser($userActor, "boardworks");

        //Act
        [$projectMetamodel, $projectName, $projectValidationThreshold, $projectDescription, $projectGroups] = $this->addProjectRequest();

        //Assert
        $project = static::getContainer()->get(ProjectRepository::class)->findOneBy([
            "metamodel" => $projectMetamodel,
            "name" => $projectName,
            'template' => false,
        ]);
        static::assertNotNull($project);
    }

    public function testAddSingleTemplateProject(): void
    {
        //Arrange
        $userActor = (new UserBuilder($this->entityManager))->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        //Login
        $this->client->loginUser($userActor, "boardworks");

        //Act
        [$projectMetamodel, $projectName, $projectDescription] = $this->addTemplateProjectRequest();

        //Assert
        $project = static::getContainer()->get(ProjectRepository::class)->findOneBy([
            "metamodel" => $projectMetamodel,
            "name" => $projectName,
            'template' => true,
        ]);
        static::assertNotNull($project);
    }

    private function addProjectRequest($metamodelId = null, $name = null, $validationThreshold = null, $description = null, $groups = null): array
    {
        $projectMetamodelId = $metamodelId ?? Constants::SAMM_ID;
        $projectName = $name ?? bin2hex(random_bytes(10));
        $projectValidationThreshold = $validationThreshold ?? 0;
        $projectDescription = $description ?? '';
        $projectGroups = $groups ?? [];

        $this->client->request("POST", "/project/add", [
            'project' => [
                "metamodel" => $projectMetamodelId,
                "name" => $projectName,
                "validationThreshold" => $projectValidationThreshold,
                "description" => $projectDescription,
                "groups" => $projectGroups,
            ],
        ]);

        return [$projectMetamodelId, $projectName, $projectValidationThreshold, $projectDescription, $projectGroups];
    }

    private function addTemplateProjectRequest($metamodelId = null, $name = null, $description = null): array
    {
        $projectMetamodelId = $metamodelId ?? Constants::SAMM_ID;
        $projectName = $name ?? bin2hex(random_bytes(10));
        $projectDescription = $description ?? '';

        $this->client->request("POST", "/project/addTemplate", [
            'template_project' => [
                "metamodel" => $projectMetamodelId,
                "name" => $projectName,
                "description" => $projectDescription,
            ],
        ]);

        return [$projectMetamodelId, $projectName, $projectDescription];
    }

    /**
     * @dataProvider testUnarchiveShowsCorrectProjectsProvider
     * @testdox $_dataName
     */
    public function testUnarchiveShowsCorrectProjects(array $entitiesToSave, User $user, Project $project, string $expectedProjectName, string $notExpectedProjectName, bool $isArchived): void
    {
        foreach ($entitiesToSave as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        $this->client->request("GET", $this->urlGenerator->generate("app_project_index"), ["archived" => ($isArchived) ? 1 : 0]);

        self::assertSelectorTextNotContains(".projects-table", $notExpectedProjectName);
        self::assertSelectorTextContains(".projects-table", $expectedProjectName);
    }

    private function testUnarchiveShowsCorrectProjectsProvider(): array
    {
        $user = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $project1 = (new Project());
        $project1Name = "Random project name ".bin2hex(random_bytes(5));
        $assessment = (new Assessment())->setProject($project1);
        $project1->setAssessment($assessment)
            ->setName($project1Name)
            ->setMetamodel((new Metamodel()));

        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $project2 = (new Project());
        $assessment2 = (new Assessment())->setProject($project2);
        $project2Name = "Random project name ".bin2hex(random_bytes(5));
        $project2
            ->setAssessment($assessment2)
            ->setName($project2Name)
            ->setMetamodel((new Metamodel()))
            ->setDeletedAt(new \DateTime());

        return [
            "Positive test 1 - test that the project is visible at /project/index when archived is 0" => [
                [$user, $user2, $project1, $project2],
                $user, // user
                $project1, // project
                $project1Name, // expected project name
                $project2Name, // expected project name to be missing
                false, // is archived
            ],
            "Positive test 2 - test that the project is visible at /project/index when archived is 1" => [
                [$user, $user2, $project1, $project2],
                $user2, // user
                $project2, // project
                $project2Name, // expected project name
                $project1Name, // expected project name to be missing
                true, // is archived
            ],
        ];
    }

    /**
     * @group asvs
     * @dataProvider ajaxModifyFloatOverflowProvider
     * @testdox ASVS 5.4.3 - $_dataName
     */
    public function testAjaxModifyFloatOverflow(array $entitiesToPersist, User $user, Project $project, string $name, bool $expectThreshHandle): void
    {
        $this->persistEntities(...$entitiesToPersist);

        $this->client->loginUser($user, 'boardworks');

        $this->client->request(Request::METHOD_POST, $this->urlGenerator->generate('app_project_ajaxmodify', ['id' => $project->getId()]), [
            'name' => $name,
            'value' => PHP_FLOAT_MAX + PHP_FLOAT_MAX / 4,
        ]);

        $response = $this->client->getResponse();

        if ($expectThreshHandle) {
            self::assertEquals(0.00, $project->getValidationThreshold());
        } else {
            self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }

    public function ajaxModifyFloatOverflowProvider(): \Generator
    {
        yield "Test 1 - test that ajaxmodify with name 'validationThreshold' handles a value > php_max_float and proceeds with it's logic without throwing an exception" => [
            [
                $user = (new UserBuilder())->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
                $project = (new ProjectBuilder())->build(),
            ],
            $user,
            $project,
            'validationThreshold',
            // threshhold new value
            true, // expect validationThreshold handle
        ];
        yield "Test 2 - test that ajaxmodify with name 'name' handles a value > php_max_float and proceeds with it's logic without throwing an exception" => [
            [
                $user = (new UserBuilder())->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
                $project = (new ProjectBuilder())->build(),
            ],
            $user,
            $project,
            'name',
            false, // expect validationThreshold handle
        ];
        yield "Test 3 - test that ajaxmodify with name 'description' handles a value > php_max_float and proceeds with it's logic without throwing an exception" => [
            [
                $user = (new UserBuilder())->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
                $project = (new ProjectBuilder())->build(),
            ],
            $user,
            $project,
            'description',
            false, // expect validationThreshold handle
        ];
    }

    /**
     * @group asvs
     * @dataProvider ajaxModifyFieldsProvider
     * @testdox ASVS 5.1.2 - $_dataName
     */
    public function testAjaxModifyFields(array $entitiesToPersist, User $user, Project $project, string $name, string $expectedResponseMsg, bool $expectReadOnly): void
    {
        $this->persistEntities(...$entitiesToPersist);

        $this->client->loginUser($user, 'boardworks');

        $this->client->request(Request::METHOD_POST, $this->urlGenerator->generate('app_project_ajaxmodify', ['id' => $project->getId()]), [
            'name' => $name,
            'value' => random_int(1, 3),
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if ($expectReadOnly) {
            self::assertEquals($expectedResponseMsg, $response->msg);
        } else {
            self::assertEquals($expectedResponseMsg, $response->status);
        }
    }

    public function ajaxModifyFieldsProvider(): \Generator
    {
        yield "Test 1 - Test that a user cannot modify a field 'externalId', which is set as read only field" => [
            [
                $user = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
                $project = (new ProjectBuilder())->build(),
                $group = (new GroupBuilder())->build(),
                (new GroupUser)->setUser($user)->setGroup($group),
                (new GroupProject())->setProject($project)->setGroup($group),
            ],
            $user,
            $project,
            'externalId', // field
            'The field is readonly!', // expected response message
            true, // expect read only
        ];
        yield "Test 2 - Test that a user can modify a field 'name', which is not set as read only field" => [
            [
                $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
                $project2 = (new ProjectBuilder())->build(),
                $group2 = (new GroupBuilder())->build(),
                (new GroupUser)->setUser($user2)->setGroup($group2),
                (new GroupProject())->setProject($project2)->setGroup($group2),
            ],
            $user2,
            $project2,
            'name', // field
            'ok', // expected response message
            false, // expect read only
        ];
        yield "Test 3 - Test that a user can modify a field 'description', which is not set as read only field" => [
            [
                $user3 = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
                $project3 = (new ProjectBuilder())->build(),
                $group3 = (new GroupBuilder())->build(),
                (new GroupUser)->setUser($user3)->setGroup($group3),
                (new GroupProject())->setProject($project3)->setGroup($group3),
            ],
            $user3,
            $project3,
            'description', // field
            'ok', // expected response message
            false, // expect read only
        ];
        yield "Test 4 - Test that a user cannot modify a field 'organization', which is set as read only field" => [
            [
                $user4 = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
                $project4 = (new ProjectBuilder())->build(),
                $group4 = (new GroupBuilder())->build(),
                (new GroupUser)->setUser($user4)->setGroup($group4),
                (new GroupProject())->setProject($project4)->setGroup($group4),
            ],
            $user4,
            $project4,
            'organization', // field
            'The field is readonly!', // expected response message
            true, // expect read only
        ];
        yield "Test 5 - Test that a user cannot modify a field 'organization', which is set as read only field" => [
            [
                $user5 = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
                $project5 = (new ProjectBuilder())->build(),
                $group5 = (new GroupBuilder())->build(),
                (new GroupUser)->setUser($user5)->setGroup($group5),
                (new GroupProject())->setProject($project5)->setGroup($group5),
            ],
            $user5,
            $project5,
            'template', // field
            'The field is readonly!', // expected response message
            true, // expect read only
        ];
    }

}
