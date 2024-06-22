<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;
use App\Service\ConfigurationService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProfileControllerTest extends AbstractWebTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $email = bin2hex(random_bytes(5));
        $this->user = (new UserBuilder($this->entityManager))->withEmail($email."@test.test")->build();
    }

    /**
     * @group asvs
     * @testdox Authentication(v4.0.3-2.1.5 & v4.0.3-2.1.6) Verify users can change their password and that password change functionality requires the user's current and new password
     */
    public function testCanChangePassword(): void
    {
        // Arrange
        $this->client->loginUser($this->user, "boardworks");

        // Act
        $this->client->request("GET", "/profile/changePassword");
        $newPassword = "n3wP@ssword!1";

        $oldPasswordHash = $this->user->getPassword();
        $this->client->submitForm(
            "Save",
            [
                "change_password[oldPassword]" => "admin123",
                "change_password[newPassword][first]" => $newPassword,
                "change_password[newPassword][second]" => $newPassword,
            ]
        );

        // NOTE:
        // Refreshing the user instance
        $this->user = static::getContainer()->get(UserRepository::class)->findOneBy([
            "id" => $this->user->getId(),
        ]);

        $newPasswordHash = $this->user->getPassword();
        self::assertNotEquals($newPasswordHash, $oldPasswordHash);
    }

    /**
     * @dataProvider incorrectPasswordProvider
     * @group asvs
     * @testdox $_dataName
     * @param string $oldPassword
     * @param string $newPassword
     * @param string $passwordConfirmation
     * @param string $errorMessage
     */
    public function testCannotChangePasswordWithoutOldPassword(
        string $errorMessage,
        string $oldPassword = "admin123",
        string $newPassword = "n3wP@ssword!1",
        string $passwordConfirmation = "n3wP@ssword!1"
    ): void {
        // Arrange
        $this->client->loginUser($this->user, "boardworks");

        // Act
        $this->client->request("GET", "/profile/changePassword");

        $this->client->submitForm(
            "Save",
            [
                "change_password[oldPassword]" => $oldPassword,
                "change_password[newPassword][first]" => $newPassword,
                "change_password[newPassword][second]" => $passwordConfirmation,
            ]
        );
        self::assertSelectorTextContains("span.form-error-message", $errorMessage);
    }

    private function incorrectPasswordProvider(): array
    {
        return [
            "Authentication(v4.0.3-2.1.5) Verify that the user cannot reset their password with an empty string as the old password" => [
                "This value should be the user's current password.",
                "",
            ],
            "Authentication(v4.0.3-2.1.5) Verify that the user cannot reset their password with an incorrect old password" => [
                "This value should be the user's current password.",
                "not-admin",
            ],
            "Authentication(v4.0.3-2.1.5) Verify that the user cannot reset their password when the new password and password confirmation don't match" => [
                "The values do not match.",
                "admin123",
                "1",
                "2",
            ],
            "Authentication (v4.0.3-2.1.7) when the new password is from a set of breached passwords" => [
                "This password has been leaked in a data breach, it must not be used. Please use another password.",
                "admin123",
                "adminpassword",
                "adminpassword",
            ],
        ];
    }

    /**
     * @group asvs
     * @dataProvider changePasswordProvider
     * @testdox ASVS 2.1.1 - $_dataName
     */
    public function testChangePassword(array $entitiesToPersist, User $user, string $oldPassword, string $newPassword, array $expectedValidationErrors, bool $expectedError): void
    {
        foreach ($entitiesToPersist as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $this->client->loginUser($user, 'boardworks');

        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('app_profile_change_password'));

        $this->client->submitForm('Save', [
            'change_password[oldPassword]' => $oldPassword,
            'change_password[newPassword][first]' => $newPassword,
            'change_password[newPassword][second]' => $newPassword,
        ]);

        if ($expectedError) {
            $errorCrawler = $this->client->getCrawler()->filter('.form-error-message');
            $errorCrawlerOldPassword = $this->client->getCrawler()->filter('[id^="change_password_oldPassword"]');
            $errors = [];
            foreach ($errorCrawler->getIterator() as $node) {
                $errors[] = $node->nodeValue;
            }
            foreach ($errorCrawlerOldPassword->getIterator() as $node) {
                $errors[] = $node->nodeValue;
            }
            foreach ($expectedValidationErrors as $error) {
                self::assertContains($error, $errors);
            }
        } else {
            $resultUser = $this->entityManager->getRepository(User::class)->find($user->getId());
            self::assertNotEquals($user->getPassword(), $resultUser->getPassword());
            self::assertTrue(password_verify($newPassword, $resultUser->getPassword()));
        }
    }

    private function changePasswordProvider(): \Generator
    {
        yield "Test 1 - expects no error password change should be successful" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            "admin123", // current password
            "AJeajkaSu%32AJkl#1", // new password
            [], // expected validation errors
            false, // expected error
        ];
        yield "Test 2 - expects error since the new password is too short, does not contain and upper case letter and is breached" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            "admin123", // current password
            "asdasd123", // new password
            [
                "This password has been leaked in a data breach, it must not be used. Please use another password.",
                "This value is too short. It should have 12 characters or more.",
                "Your password must contain at least one uppercase letter",
            ], // expected validation errors
            true, // expected error
        ];
        yield "Test 3 - expects error since the new password is too short, does not contain numbers" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            "admin123", // current password
            "asdasd", // new password
            [
                "This password has been leaked in a data breach, it must not be used. Please use another password.",
                "This value is too short. It should have 12 characters or more.",
                "Your password must contain at least one digit",
            ], // expected validation errors
            true, // expected error
        ];
        yield "Test 4 - expects error since the current password is incorrect" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            "wrongpassword", // current password
            "AJeajkaSu%32AJkl#1", // new password
            [
                "This value should be the user's current password.",
            ], // expected validation errors
            true, // expected error
        ];
    }

    /**
     * @group asvs
     * @dataProvider validateFormDataProvider
     * @testdox ASVS 5.1.3 - $_dataName
     */
    public function testValidateFormData(array $entitiesToPersist, User $user, string $name, string $surname, bool $expectSuccess): void
    {
        $this->persistEntities(...$entitiesToPersist);

        $this->client->loginUser($user, 'boardworks');

        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('app_profile_profile'));
        $this->client->submitForm('Save', [
            'user[name]' => $name,
            'user[surname]' => $surname,
        ]);

        /** @var User $newUser */
        $newUser = $this->entityManager->getRepository(User::Class)->find($user->getId());
        self::assertNotNull($newUser);

        if ($expectSuccess) {
            self::assertEquals($name, $newUser->getName());
            self::assertEquals($surname, $newUser->getSurname());
        } else {
            self::assertNotEquals($newUser->getName(), $user->getName());
            self::assertNotEquals($newUser->getName(), $user->getSurname());
        }
    }

    private function validateFormDataProvider(): \Generator
    {
        yield "Test 1 - Expects to be rejected since name and surname should never be blank" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            '', // name
            'ddd', // surname
            false, // expects success
        ];
        yield "Test 2 - Expects to be rejected since name should never be blank" => [
            [
                $user2 = (new UserBuilder())->build(),
            ],
            $user2,
            '', // name
            'test', // surname
            false, // expects success
        ];
        yield "Test 3 - Expects to be rejected since surname should never be blank" => [
            [
                $user3 = (new UserBuilder())->build(),
            ],
            $user3,
            'asd', // name
            '', // surname
            false, // expects success
        ];

        yield "Test 4 - Expects to be successful" => [
            [
                $user4 = (new UserBuilder())->build(),
            ],
            $user4,
            'test', // name
            'test', // surname
            true, // expects success
        ];
    }

}
