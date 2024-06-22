<?php

namespace App\Tests\functional;

use App\Entity\Group;
use App\Entity\GroupUser;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use App\Service\SanitizerService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\GroupBuilder;
use App\Tests\builders\UserBuilder;
use App\Tests\helpers\TestHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserControllerTest extends AbstractWebTestCase
{

    private ?User $userInOrganizationAndManager = null;
    private ?User $userInOrganizationAndRegularUser = null;
    private ?User $userInAnotherOrganizationAndManager = null;
    private ?User $userInAnotherOrganizationAndRegularUser = null;
    private ?User $userToEditSameOrg = null;
    private ?User $userToEditAnotherOrg = null;
    private ?Group $group = null;

    private function setupUsers(): void
    {
        $this->group = (new GroupBuilder())->build();
        $this->userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $this->userInOrganizationAndRegularUser = (new UserBuilder())->build();
        $this->userInAnotherOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $this->userInAnotherOrganizationAndRegularUser = (new UserBuilder())->build();
        $this->userToEditSameOrg = (new UserBuilder())->build();
        $this->userToEditAnotherOrg = (new UserBuilder())->build();
    }


    /**
     * @group asvs
     * @dataProvider testAjaxModifyEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testAjaxModifyEndpointDOA(User $user, User $chosenUser, array $payload, array $headers, int $expectedStatusCode): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($chosenUser);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", "/user/ajaxModify/".$chosenUser->getId(), $payload, [], $headers);

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualStatusCode);
    }

    private function testAjaxModifyEndpointDOAProvider(): array
    {
        $this->setupUsers();

        return [
            "Positive 1 - Test that modifying ajax at '/ajaxModify/{id}' is allowed for a user, who is IN the org and HAS manager role and is trying to edit a user from the same org" => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'email',
                    'value' => 'new_email'.bin2hex(random_bytes(5)).'@asd.com',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that modifying ajax at '/ajaxModify/{id}' is not allowed for a user, who is IN the org, but DOES NOT have a manager role and is trying to edit another user in the same org" => [
                $this->userInOrganizationAndRegularUser, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'email',
                    'value' => 'new_email'.bin2hex(random_bytes(5)).'@asd.com',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that modifying ajax at '/ajaxModify/{id}' is not allowed for a user, who is NOT in the org and he is a regular user and is trying to edit a user in the current org" => [
                $this->userInAnotherOrganizationAndRegularUser, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'email',
                    'value' => 'new_email'.bin2hex(random_bytes(5)).'@asd.com',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider testAjaxModifyReadonlyFieldsProvider
     * @testdox AjaxModify of readonly fields $_dataName
     */
    public function testAjaxModifyReadonlyFields(
        User $user,
        User $chosenUser,
        array $payload,
        array $headers,
        bool $expectPassword,
    ): void {
        $this->entityManager->persist($user);
        $this->entityManager->persist($chosenUser);
        $this->entityManager->flush();

        $expectedValue = $chosenUser->getPasswordResetHash();
        if ($expectPassword) {
            $expectedValue = $chosenUser->getPassword();
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", "/user/ajaxModify/".$chosenUser->getId(), $payload, [], $headers);

        $userRepository = self::getContainer()->get(UserRepository::class);
        $chosenUser = $userRepository->find($chosenUser->getId());

        $userPropGetter = "get".$payload["name"];
        self::assertEquals($chosenUser->$userPropGetter(), $expectedValue);
    }

    private function testAjaxModifyReadonlyFieldsProvider(): array
    {
        $this->setupUsers();

        return [
            "Negative 1 - Test that modifying ajax at '/ajaxModify/{id}' won't change password" => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'passworD',
                    'value' => 'fakeValue',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                true, // expected value
            ],
            "Negative 2 - Test that modifying ajax at '/ajaxModify/{id}' won't change password" => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'PASSWORD',
                    'value' => 'fakeValue',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                true, // expected value
            ],
            "Negative 3 - Test that modifying ajax at '/ajaxModify/{id}' won't change password" => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => "passwordRESETHASH",
                    'value' => 'fakeValue',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                false, // expected value
            ],
        ];
    }

    /**
     * @group asvs
     * @dataProvider testDeleteEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testDeleteEndpointDOA(User $user, User $chosenUser, array $headers, int $expectedStatusCode): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($chosenUser);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }


        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request("DELETE", "/user/delete/".$chosenUser->getId());

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualStatusCode);
    }

    private function testDeleteEndpointDOAProvider(): array
    {
        $this->setupUsers();

        return [
            "Positive 1 - Test that deleting at '/{id}' is allowed for a user, who is IN the org and HAS manager role and is trying to delete a user from the same org" => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to delete
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that deleting at '/{id}' is not allowed for a user, who is IN the org, but DOES NOT have a manager role and is trying to delete another user in the same org" => [
                $this->userInOrganizationAndRegularUser, // user who edits
                $this->userToEditSameOrg, // chosen user to delete
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that deleting at '/{id}' is not allowed for a user, who is NOT in the org and he is a regular user and is trying to delete a user in the current org" => [
                $this->userInAnotherOrganizationAndRegularUser, // user who edits
                $this->userToEditSameOrg, // chosen user to delete
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }


    /**
     * @group asvs
     * @dataProvider testEditUserEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testEditUserEndpointDOA(User $user, User $chosenUser, array $headers, int $expectedStatusCode): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($chosenUser);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }


        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request("POST", "/user/editUser/".$chosenUser->getId(), [], [], $headers);

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualStatusCode);
    }

    private function testEditUserEndpointDOAProvider(): array
    {
        $this->setupUsers();

        return [
            "Positive 1 - Test that editing user at '/editUser/{id}' is allowed for a user, who is IN the org and HAS manager role and is trying to edit a user from the same org" => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'userRoles' => [Role::MANAGER->string(), Role::EVALUATOR->string()],
                    'userGroups' => [],
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that editing user at '/editUser/{id}' is not allowed for a user, who is IN the org, but DOES NOT have a manager role and is trying to edit another user in the same org" => [
                $this->userInOrganizationAndRegularUser, // user who edits
                $this->userToEditSameOrg, // chosen user to delete
                [
                    'userRoles' => [Role::MANAGER->string(), Role::EVALUATOR->string()],
                    'userGroups' => [],
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that editing user at '/editUser/{id}' is not allowed for a user, who is NOT in the org and he is a regular user and is trying to edit a user in the current org" => [
                $this->userInAnotherOrganizationAndRegularUser, // user who edits
                $this->userToEditSameOrg, // chosen user to delete
                [
                    'userRoles' => [Role::MANAGER->string(), Role::EVALUATOR->string()],
                    'userGroups' => [],
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }

    /**
     * @param User $user
     * @param User $chosenUser
     * @param array $payload
     * @param array $headers
     * @param int $expectedResponseCode
     * @dataProvider testAjaxModifyEmailExistsProvider
     * @return void
     * @throws \Exception
     */
    public function testAjaxModifyEmailExists(User $user, User $chosenUser, array $payload, array $headers, int $expectedStatusCode): void
    {
        (new UserBuilder($this->entityManager))->withEmail("existingEmail@abv.bg")->build();
        $this->entityManager->persist($user);
        $this->entityManager->persist($chosenUser);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $oldEmail = $chosenUser->getEmail();
        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", "/user/ajaxModify/".$chosenUser->getId(), $payload, [], $headers);

        $actualResponseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualResponseCode);

        $chosenUser = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $chosenUser->getId()]);
        if ($expectedStatusCode == Response::HTTP_BAD_REQUEST) {
            self::assertEquals($oldEmail, $chosenUser->getEmail());
        } else {
            self::assertEquals($payload["value"], $chosenUser->getEmail());
        }
    }

    private function testAjaxModifyEmailExistsProvider(): array
    {
        $this->setupUsers();

        return [
            "Positive 1 - passing a new non existing email " => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'email',
                    'value' => 'new_email'.bin2hex(random_bytes(5)).'@asd.com',
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_OK, // expected response
            ],
            "Negative 1 - passing an existing email " => [
                $this->userInOrganizationAndManager, // user who edits
                $this->userToEditSameOrg, // chosen user to edit
                [
                    'name' => 'email',
                    'value' => "existingEmail@abv.bg",
                ], //payload
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/user/index",
                ], //headers
                Response::HTTP_BAD_REQUEST, // expected response
            ],
        ];
    }

    public function testAddSingleUser(): void
    {
        //Arrange
        $userActor = (new UserBuilder($this->entityManager))->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        //Login
        $this->client->loginUser($userActor, "boardworks");

        //Act
        [$userName, $userSurname, $userEmail, $userRoles, $userGroups] = $this->addUserRequest();

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(["name" => $userName, 'surname' => $userSurname]);

        //Assert
        static::assertNotNull($user);
    }

    private function addUserRequest($name = null, $surname = null, $email = null, $roles = null, $groups = null): array
    {
        $userName = $name ?? bin2hex(random_bytes(10));
        $userSurname = $surname ?? bin2hex(random_bytes(10));
        $userEmail = $email ?? bin2hex(random_bytes(10)).'@test.com';
        $userRoles = $roles ?? [];
        $userGroups = $groups ?? [];

        $this->client->request("POST", "/user/add", [
            'user_add' => [
                "name" => $userName,
                "surname" => $userSurname,
                "email" => $userEmail,
                "roles" => $userRoles,
                "groups" => $userGroups,
            ],
        ]);

        return [$userName, $userSurname, $userEmail, $userRoles, $userGroups];
    }

    /**
     * @dataProvider testManagerRemoveHisOwnManagerRoleProvider
     * @testdox $_dataName
     */
    public function testManagerRemoveHisOwnManagerRole(User $manager, User $managerToEdit, array $newUserRoles, bool $expectedUserManagerRole): void
    {
        $this->entityManager->persist($manager);
        $this->entityManager->persist($managerToEdit);
        $this->entityManager->flush();

        $this->client->loginUser($manager, "boardworks");

        self::assertContains("ROLE_MANAGER", $managerToEdit->getRoles());

        $this->client->request("POST", $this->urlGenerator->generate("app_user_edituser", ['id' => $managerToEdit->getId()]), [$newUserRoles]);

        $managerToEditFromDb = static::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class)->find($managerToEdit->getId());

        if ($expectedUserManagerRole) {
            self::assertContains("ROLE_MANAGER", $managerToEditFromDb->getRoles());
        } else {
            self::assertNotContains("ROLE_MANAGER", $managerToEditFromDb->getRoles());
        }
    }

    private function testManagerRemoveHisOwnManagerRoleProvider(): array
    {
        $manager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $anotherManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        return [
            "Positive Test 1 - ROLE_MANAGER is still present even when a manager tries to remove it's own role" => [
                $manager, // logged in manager
                $manager, // manager to edit
                [Role::USER->string()], // new roles to edit
                true, // expected a user manager role to be present
            ],
            "Positive Test 2 - ROLE_MANAGER is not present when a manager tries to remove another manager's role" => [
                $manager, // logged in manager
                $anotherManager, // manager to edit
                [Role::USER->string()], // new roles to edit
                false, // expected a user manager role to be present
            ],
        ];
    }

    /**
     * @group asvs
     * @dataProvider xssIsBeingEscapedFromTwigRenderProvider
     * @testdox ASVS 1.5.4 - $_dataName
     */
    public function testXSSisBeingEscapedFromTwigRender(array $entitiesToPersist, User $user, string $expectedUserName, string $expectedColumnHeaderLabel): void
    {
        $this->entityManager->getConnection()->executeStatement('DELETE FROM `user`;');
        $sanitizerServiceMock = $this->createPartialMock(SanitizerService::class, ['sanitizeValue']);
        $sanitizerServiceMock->method('sanitizeValue')->willReturnCallback(function ($value) {
            return $value;
        });

        $existingListeners = $this->entityManager->getEventManager()->getListeners(Events::prePersist);
        foreach ($existingListeners as $existingListener) {
            $this->entityManager->getEventManager()->removeEventListener(Events::prePersist, $existingListener);
        }

        foreach ($entitiesToPersist as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $this->client->loginUser($user, 'boardworks');

        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('app_user_index'));

        $crawler = $this->client->getCrawler();

        self::assertSelectorTextContains('.name-'.$user->getId(), $expectedUserName);

        $columnHeaderLabelAfterXSS = $crawler->filter('#column-name-label')->getNode(0)->nodeValue;
        self::assertEquals($expectedColumnHeaderLabel, $columnHeaderLabelAfterXSS);
    }

    public function xssIsBeingEscapedFromTwigRenderProvider(): \Generator
    {
        yield "Test 1 - a user with XSS as a name should be escaped by the twig renderer" => [
            [
                $user = (new UserBuilder())->withName('testname<script>document.getElementById("column-name-label").textContent = "malicious text";</script>')->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
                $group = (new GroupBuilder())->build(),
                $groupUser = (new GroupUser)->setUser($user)->setGroup($group),
            ],
            $user,
            'testname<script>document.getElementById("column-name-label").textContent = "malicious text";</script> Manolov', // expected user name in db
            'Name', // expected column header after xss injection
        ];
        yield "Test 2 - a user with XSS as a name should be escaped by the twig renderer" => [
            [
                $user = (new UserBuilder())
                    ->withName(
                        'anotheruser<script>document.getElementById("column-name-label").textContent = "<script>document.getElementById(\'column-name-label\').textContent = \'internal malicious text\'</script>";</script>'
                    )->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
                $group = (new GroupBuilder())->build(),
                $groupUser = (new GroupUser)->setUser($user)->setGroup($group),
            ],
            $user,
            'anotheruser<script>document.getElementById("column-name-label").textContent = "<script>document.getElementById(\'column-name-label\').textContent = \'internal malicious text\'</script>";</script> Manolov',
            // expected user name in db
            'Name',
            // expected column header after xss injection
        ];
    }

    /**
     * @group asvs
     * @dataProvider editUserProvider
     * @testdox ASVS 4.1.2 - $_dataName
     */
    public function testEditUser(array $entitiesToPersist, User $userWhoDoesTheAction, User $userWhoIsBeingEdited, array $newRoles, string $expectedRemovedRole, bool $expectAccessDenied): void
    {
        $this->persistEntities(...$entitiesToPersist);

        if ($expectAccessDenied) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($userWhoDoesTheAction, 'boardworks');

        $rolesBefore = $userWhoIsBeingEdited->getRoles();
        sort($rolesBefore);

        $this->client->request(
            Request::METHOD_POST, $this->urlGenerator->generate('app_user_edituser', ['id' => $userWhoIsBeingEdited->getId()]),
            parameters: [
                'userRoles' => $newRoles,
            ]
        );

        $rolesAfter = $userWhoIsBeingEdited->getRoles();
        sort($rolesAfter);
        sort($newRoles);

        self::assertNotEquals($rolesBefore, $rolesAfter);
        self::assertEquals($rolesAfter, $newRoles);
        self::assertArrayNotHasKey($expectedRemovedRole, $rolesAfter);
    }

    public function editUserProvider(): \Generator
    {
        yield "Test 1 - Test that a manager from the same organization successfully edits roles for another user in the org" => [
            [
                $userManager = (new UserBuilder())
                    ->withRoles([Role::USER->string(), Role::MANAGER->string()])
                    ->build(),
                $userToEdit = (new UserBuilder())
                    ->withRoles([Role::USER->string(), Role::EVALUATOR->string(), Role::VALIDATOR->string()])
                    ->build(),
            ],
            $userManager,
            $userToEdit,
            [Role::USER->string(), Role::EVALUATOR->string()], // new roles for the user to edit
            Role::VALIDATOR->string(), // expected removed role
            false, // expect access denied
        ];
        yield "Test 2 - Test that a non manager user gets access denied when trying to edit roles of another user" => [
            [
                $userNonManager = (new UserBuilder())->withRoles([Role::USER->string()])->build(),
                $userToEdit2 = (new UserBuilder())
                    ->withRoles([Role::USER->string(), Role::EVALUATOR->string(), Role::VALIDATOR->string()])
                    ->build(),
            ],
            $userNonManager,
            $userToEdit2,
            [Role::USER->string(), Role::EVALUATOR->string()], // new roles for the user to edit
            Role::VALIDATOR->string(), // expected removed role
            true, // expect access denied
        ];
    }

    /**
     * @group asvs
     * @dataProvider ajaxModifyUnicodeProvider
     * @testdox ASVS 5.3.1 - $_dataName
     */
    public function testAjaxModifyUnicode(array $entitiesToPersist, User $user, string $unicodeToSave, string $expectedUnicodeOutput): void
    {
        $this->persistEntities(...$entitiesToPersist);

        $this->client->loginUser($user, 'boardworks');

        $this->client->request(Request::METHOD_POST, $this->urlGenerator->generate('app_user_ajaxmodify', ['id' => $user->getId()]), parameters: [
            'name' => 'surname',
            'value' => $unicodeToSave,
        ]);
        $this->entityManager->clear();

        $userFromDb = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertEquals($unicodeToSave, $userFromDb->getSurname());

        $this->client->request(Request::METHOD_POST, $this->urlGenerator->generate('app_profile_profile'));
        self::assertEquals($expectedUnicodeOutput, $this->client->getCrawler()->filter('#user_surname')->attr('value'));
    }

    private function ajaxModifyUnicodeProvider(): \Generator
    {
        yield "Test 1 - Test that saving unicode symbols as a surname are saved in the db and then output as expected" => [
            [
                $user = (new UserBuilder())->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
            ],
            $user,
            TestHelper::generateUTF8String(6, 0x1f601), // unicode to save
            '😁😂😃😄😅😆', // expected unicode output
        ];
        yield "Test 2 - Test that saving unicode symbols as a surname are saved in the db and then output as expected" => [
            [
                $user = (new UserBuilder())->withRoles(['ROLE_USER', 'ROLE_MANAGER'])->build(),
            ],
            $user,
            TestHelper::generateUTF8String(12, 0x1f601), // unicode to save
            '😁😂😃😄😅😆😇😈😉😊😋😌', // expected unicode output
        ];
    }

}