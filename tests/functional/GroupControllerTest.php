<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\Group;
use App\Entity\GroupUser;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\GroupProjectRepository;
use App\Repository\GroupRepository;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GroupControllerTest extends AbstractWebTestCase
{
    private ?User $user = null;
    private ?User $manager1 = null;
    private ?User $manager2 = null;
    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();


        $this->user = (new User())
            ->setRoles([Role::USER->string()])
            ->setEmail("user@user.user")
            ->setAgreedToTerms(true)
            ->setSecretKey('MFZWIZDEMRSQ====');

        $this->manager1 = (new User())
            ->setRoles([Role::USER->string(), Role::MANAGER->string()])
            ->setEmail("manager@manager.manager")
            ->setAgreedToTerms(true)
            ->setSecretKey('MFZWIZDEMRSQ====');

        $this->manager2 = (new User())
            ->setRoles([Role::USER->string(), Role::MANAGER->string()])
            ->setEmail("manager2@manager2.manager2")
            ->setAgreedToTerms(true)
            ->setSecretKey('MFZWIZDEMRSQ====');

        $this->group = (new Group())->setName("test group");
        $groupUser = (new GroupUser())->setGroup($this->group)->setUser($this->user);
        $groupUser2 = (new GroupUser())->setGroup($this->group)->setUser($this->manager1);

        $this->entityManager->persist($this->group);
        $this->entityManager->persist($groupUser);
        $this->entityManager->persist($groupUser2);
        $this->entityManager->persist($this->manager2);
        $this->entityManager->flush();
    }

    /**
     * @param User $user
     * @return ?Group
     * @throws Exception
     */
    private function createGroupAs(User $user, bool $expectException): ?Group
    {
        if ($expectException) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");
        $groupName = bin2hex(random_bytes(10));

        $this->client->request("POST", "/group/add", [
            'group' => ["name" => $groupName],
        ]);

        return static::getContainer()->get(GroupRepository::class)->findOneBy(["name" => $groupName]);
    }

    /**
     * @testdox Manager attempts to create group, expect creation
     * @throws Exception
     */
    public function testAddByManagerIsSuccessful()
    {
        $group = $this->createGroupAs($this->manager1, false);
        self::assertNotNull($group);
    }

    /**
     * @testdox Normal user attempts to create group, expect NO creation
     * @throws Exception
     */
    public function testAddByNormalUserIsNotSuccessful()
    {
        $group = $this->createGroupAs($this->user, true);
        self::assertNull($group);
    }

    private function postToAjaxModifyAs(User $user, bool $expectException)
    {
        // Arrange
        if ($expectException) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");
        $payload = [
            'name' => 'name',
            'value' => 'newGroupName-'.$this->group->getId(),
        ];
        // Act
        $this->client->request("POST", "/group/ajaxModify/".$this->group->getId(), $payload);
    }

    /**
     * @testdox Manager attempts to edit group in his organization, expect success
     * @throws Exception
     */
    public function testAjaxModifyAsManagerOfTheGroup()
    {
        $this->postToAjaxModifyAs($this->manager1, false);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @testdox Normal user attempts to edit group, expect failure
     * @throws Exception
     */
    public function testAjaxModifyAsNormalUser()
    {
        $this->postToAjaxModifyAs($this->user, true);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider ajaxmodifyEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testAjaxmodifyEndpointDOA(User $user, Group $group, int $expectedStatusCode)
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        if ($expectedStatusCode !== Response::HTTP_OK) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $payload = [
            'name' => 'name',
            'value' => 'newGroupName-'.$this->group->getId(),
        ];

        $this->client->request("POST", $this->urlGenerator->generate("app_group_ajaxmodify", ['id' => $group->getId()]), $payload);

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($actualStatusCode, $expectedStatusCode);
    }

    public function ajaxmodifyEndpointDOAProvider(): array
    {
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegular = (new UserBuilder())->build();

        $group = (new Group());


        return [
            "Positive 1 - Test that ajax modifying at '/ajaxModify/{id}' is allowed for a user, who is IN the org and HAS a manager role" => [
                $userInOrganizationAndManager, // user
                $group, // group
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that ajax modifying at '/ajaxModify/{id}' is not allowed for a user, who is IN the org, but DOES NOT have a manager role" => [
                $userInOrganizationAndRegular, // user
                $group, // group
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }

    /**
     * @group asvs
     * @group security
     * @dataProvider testEditProjectEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testEditProjectEndpointDOA(User $user, Group $group, array $payload, int $expectedStatusCode)
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        if ($expectedStatusCode !== Response::HTTP_OK) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request("POST", $this->urlGenerator->generate("app_group_edit_projects", ['id' => $group->getId()]), $payload);

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualStatusCode);
    }

    private function testEditProjectEndpointDOAProvider(): array
    {
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegular = (new UserBuilder())->build();
        $userNotInOrganization = (new UserBuilder())->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        $group = (new Group());

        return [
            "Positive 1 - Test that editing a project at '/editProjects/{id}' is allowed for a user who, is IN the org and HAS manager role" => [
                $userInOrganizationAndManager, // user
                $group, // group
                [
                    'name' => 'name',
                    'value' => 'the new name of the project',
                ], //payload
                Response::HTTP_OK, // expected status code
            ],
            "Negative 1 - Test that editing a project at '/editProjects/{id}' is not allowed for a user, who is IN the org, but DOES NOT have a manager role" => [
                $userInOrganizationAndRegular, // user
                $group, // group
                [
                    'name' => 'name',
                    'value' => 'the new name of the project',
                ], //payload
                Response::HTTP_FORBIDDEN, // expected status code
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider testEditProjectFromDifferentOrganizationProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testEditProjectFromDifferentOrganization(User $user, Group $group, Project $attemptedProject, bool $expectation)
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->persist($attemptedProject);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request(
            "POST",
            $this->urlGenerator->generate("app_group_edit_projects", ['id' => $group->getId()]),
            [
                'projectIds' => [$attemptedProject->getId()],
            ]
        );

        $groupProjectRepository = self::getContainer()->get(GroupProjectRepository::class);
        $groupProjects = $groupProjectRepository->findBy(["group" => $group]);
        $projectIds = [];
        foreach ($groupProjects as $groupProject) {
            $projectIds[] = $groupProject->getProject()->getId();
        }

        if ($expectation) {
            self::assertContains($attemptedProject->getId(), $projectIds);
        } else {
            self::assertNotContains($attemptedProject->getId(), $projectIds);
        }
    }

    private function testEditProjectFromDifferentOrganizationProvider(): \Generator
    {
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        $project1 = (new Project())->setName("project");

        $group = (new Group());

        $project2 = new Project();

        yield "Positive 1 - Attempt to add project from same organization to my group" => [
            $userInOrganizationAndManager, // user
            $group, // group
            $project1, // attempted project
            true,
        ];
    }

    /**
     * @group asvs
     * @group security
     * @dataProvider testDeleteEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testDeleteEndpointDOA(User $user, Group $group, int $expectedStatusCode)
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        if ($expectedStatusCode !== Response::HTTP_OK) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request("DELETE", $this->urlGenerator->generate("app_group_delete", ['id' => $group->getId()]));

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualStatusCode);
    }

    private function testDeleteEndpointDOAProvider(): array
    {
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegular = (new UserBuilder())->build();
        $userNotInOrganization = (new UserBuilder())->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        $group = (new Group());

        return [
            "Positive 1 - Test that deleting a group at '/{id}' is allowed for a user, who is IN the org and HAS manager role" => [
                $userInOrganizationAndManager, // user
                $group, // group
                Response::HTTP_OK, // expected status code
            ],
            "Negative 1 - Test that deleting a group at '/{id}' is not allowed for a user, who is IN the org, but DOES NOT have a manager role" => [
                $userInOrganizationAndRegular, // user
                $group, // group
                Response::HTTP_FORBIDDEN, // expected status code
            ],
        ];
    }
}
