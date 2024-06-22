<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Mailing;
use App\Entity\User;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\builders\UserBuilder;

class UserTest extends AbstractKernelTestCase
{
    /**
     * @group asvs
     * @dataProvider passwordChangeTriggersMailingProvider
     * @testdox ASVS 2.2.3 - $_dataName
     */
    public function testPasswordChangeTriggersMailing(array $entitiesToPersist, User $user, string $newPassword, bool $expectedMail): void
    {
        $this->persistEntities(...$entitiesToPersist);

        $mail = $this->entityManager->getRepository(Mailing::class)->findOneBy([
            "email" => $user->getEmail(),
            "subject" => "Your SAMMY login credentials have been changed",
        ]);

        self::assertNull($mail);

        $user->setPassword($newPassword);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $mail = $this->entityManager->getRepository(Mailing::class)->findOneBy([
            "email" => $user->getEmail(),
            "subject" => "Your SAMMY login credentials have been changed",
        ]);

        if ($expectedMail) {
            self::assertNotNull($mail);
            $user = $this->entityManager->getRepository(User::class)->find($user->getId());
            self::assertEquals($newPassword, $user->getPassword(), "Passwords do not match");
        } else {
            self::assertNull($mail);
        }
    }

    public function passwordChangeTriggersMailingProvider(): \Generator
    {
        yield "Test 1 - a new mailing is expected to be create when the user has his password changed" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            "newPassword",
            true,
        ];
        yield "Test 2 - a new mailing should not be sent when the user uses the same password" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            $user->getPassword(),
            false,
        ];
    }
}
