<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\NewUserDTO;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Improvement;
use App\Entity\Mailing;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\MailTemplateType;
use App\Enum\Role;
use App\Exception\InvalidUserDataException;
use App\Service\UserService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\EntityManagerTestCase;

class UserServiceTest extends AbstractKernelTestCase
{
    private UserService $userService;

    public function setUp(): void
    {
        parent::setUp();
        $this->userService = self::getContainer()->get(UserService::class);
    }

    public function testRegisterUser()
    {
        ($group = new Group())->setId(2);
        ($group2 = new Group())->setId(1);
        $this->entityManager->persist($group);
        $this->entityManager->persist($group2);
        $this->entityManager->flush();
        $user = new NewUserDTO();
        $user->name = "Test";
        $user->surname = "Tester";
        $user->email = "test@codific.com";
        $groupIds = [];
        $groupIds[] = $group->getId();
        $groupIds[] = $group2->getId();
        $user = $this->userService->registerUser($user, $groupIds);
        $this->entityManager->refresh($user);
        self::assertCount(2, $user->getGroupUsers());
    }

    public function testDeleteUser()
    {
        ($user = new User())->setName("Codific");
        $this->userService->delete($user);
        self::assertNotNull($user->getDeletedAt());
    }

    /**
     * @dataProvider userHasImproverRightsProvider
     */
    public function testUserHasImproverRights(User $user, Improvement $improvement, bool $expectedResult)
    {
        $hasRights = $this->userService->userHasImproverRights($improvement, $user);
        self::assertEquals($hasRights, $expectedResult);
    }

    public function userHasImproverRightsProvider(): array
    {
        return [
            "Positive 1 - user is improver and improvement is not started" => [
                (new User())->setRoles([Role::IMPROVER->string()]),
                (new Improvement())->setStatus(\App\Enum\ImprovementStatus::NEW),
                true,
            ],
            "Positive 2 - user is an improver and a manager, improvement is started" => [
                (new User())->setRoles([Role::MANAGER->string(), Role::IMPROVER->string()]),
                (new Improvement())->setStatus(\App\Enum\ImprovementStatus::IMPROVE)->setId(1),
                true,
            ],
            "Negative 1 - user is an improver, but improvement is started" => [
                (new User())->setRoles([Role::IMPROVER->string()]),
                (new Improvement())->setStatus(\App\Enum\ImprovementStatus::IMPROVE)->setId(1),
                false,
            ],
            "Negative 2 - user is not an improver" => [
                (new User())->setRoles([Role::MANAGER->string()]),
                (new Improvement())->setStatus(\App\Enum\ImprovementStatus::NEW)->setId(1),
                false,
            ],
        ];
    }

    public function testModifyUserRoles()
    {
        ($user = new User())->setRoles([Role::IMPROVER->string()]);
        $this->userService->modifyUserRoles($user, [Role::EVALUATOR->string(), Role::VALIDATOR->string()]);
        self::assertContains(Role::EVALUATOR->string(), $user->getRoles());
        self::assertContains(Role::VALIDATOR->string(), $user->getRoles());
        self::assertNotContains(Role::IMPROVER->string(), $user->getRoles());
        self::assertNotContains(Role::MANAGER->string(), $user->getRoles());
    }

    public function testEditUser()
    {
        ($group = new Group())->setId(2);
        ($group2 = new Group())->setId(1);
        ($user = new User())->setRoles([Role::IMPROVER->string()]);
        $user->addUserGroupUser(($groupUser = new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupUser($groupUser);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $groupIds = [$group2->getId()];
        $this->userService->editUser($user, [Role::EVALUATOR->string(), Role::VALIDATOR->string()], $groupIds);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        self::assertEquals($user->getGroupUsers()->first()->getGroup()->getId(), $group2->getId());
        self::assertContains(Role::EVALUATOR->string(), $user->getRoles());
        self::assertContains(Role::VALIDATOR->string(), $user->getRoles());
        self::assertNotContains(Role::IMPROVER->string(), $user->getRoles());
    }

    public function testAddUserToGroups()
    {
        ($group = new Group());
        ($group2 = new Group());
        ($user = new User())->setRoles([Role::IMPROVER->string()]);
        $this->entityManager->persist($group);
        $this->entityManager->persist($group2);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        self::assertCount(0, $user->getGroupUsers());
        $this->userService->addUserToGroups($user, [$group, $group2]);
        self::assertCount(2, $user->getGroupUsers());
    }

    public function testGetUsersWithProjectAccess()
    {
        $project = new Project();
        ($group = new Group())->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        ($group2 = new Group())->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group2));
        ($improver = new User())->setRoles([Role::IMPROVER->string()]);
        $improver->addUserGroupUser(($groupUser = new GroupUser())->setUser($improver)->setGroup($group));
        $group->addGroupGroupUser($groupUser);
        ($improverAndEvaluator = new User())->setRoles([Role::IMPROVER->string(), Role::EVALUATOR->string()]);
        $improverAndEvaluator->addUserGroupUser(($groupUser2 = new GroupUser())->setUser($improverAndEvaluator)->setGroup($group2));
        $improverAndEvaluator->addUserGroupUser(($groupUser3 = new GroupUser())->setUser($improverAndEvaluator)->setGroup($group));
        $group2->addGroupGroupUser($groupUser2);
        $group2->addGroupGroupUser($groupUser3);
        $this->entityManager->persist($improver);
        $this->entityManager->persist($improverAndEvaluator);
        $this->entityManager->flush();
        $improvers = $this->userService->getUsersWithProjectAccess($project, Role::IMPROVER->string());
        self::assertCount(2, $improvers);
        $evaluators = $this->userService->getUsersWithProjectAccess($project, Role::EVALUATOR->string());
        self::assertCount(1, $evaluators);
        self::assertEquals(end($evaluators), $improverAndEvaluator);
    }

    public function testWelcomeUser()
    {
        $user = (new User())->setEmail($email = rand(10000, 20000).'@codific.com');
        $this->entityManager->persist($user);
        $this->userService->welcomeUser($user, MailTemplateType::USER_WELCOME, true);
        self::assertNotEmpty($user->getPasswordResetHash());
        $this->entityManager->flush();
        self::assertCount(1, $this->entityManager->getRepository(Mailing::class)->findBy(['email' => $email]));
    }

    /**
     * @dataProvider setUserTimeZoneProvider
     */
    public function testSetUserTimeZone(User $user, ?string $timezone, ?string $expectedResult)
    {
        $this->userService->setUserTimeZone($user, $timezone);
        self::assertEquals($user->getTimeZone(), $expectedResult);
    }

    public function setUserTimeZoneProvider(): array
    {
        return [
            "Positive 1 - user has no timezone, we provide a non-empty timezone" => [
                new User(),
                "Europe/Yerevan",
                "Europe/Yerevan",
            ],
            "Positive 2 - user has no timezone, we provide an empty timezone" => [
                new User(),
                null,
                null,
            ],
            "Negative 1 - user has timezone, we provide an empty timezone" => [
                (new User())->setTimeZone("Europe/Yerevan"),
                null,
                "Europe/Yerevan",
            ],
            "Negative 2 - user has timezone, we provide a non-empty timezone" => [
                (new User())->setTimeZone("Europe/Yerevan"),
                "Europe/Sofia",
                "Europe/Yerevan",
            ],
        ];
    }
}