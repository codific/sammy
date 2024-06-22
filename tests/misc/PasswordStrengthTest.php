<?php
declare(strict_types=1);

namespace App\Tests\misc;

use App\Entity\User;
use App\Enum\Role;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\builders\UserBuilder;

class PasswordStrengthTest extends AbstractKernelTestCase
{
    /**
     * @group asvs
     * @dataProvider checkPasswordStrengthProvider
     * @testdox ASVS 2.4.1 - $_dataName
     */
    public function testCheckPasswordStrength(array $entitiesToPersist, User $user, string $expectedAlgorithm, int $expectedCostFactor): void
    {
        $this->persistEntities(...$entitiesToPersist);

        // is bcrypt pattern
        self::assertTrue((bool)preg_match('/^\$2[ayb]\$[0-9]{2}\$[A-Za-z0-9.\/]{53}$/', $user->getPassword(), $regexMatch));

        $algorithm = explode('$', $user->getPassword())[1];
        $costFactor = (int)explode('$', $user->getPassword())[2];

        self::assertEquals($expectedAlgorithm, $algorithm);
        self::assertEquals($expectedCostFactor, $costFactor);
    }

    public function checkPasswordStrengthProvider(): \Generator
    {
        yield "Test 1 - Test that user's password matches bcrypt pattern, uses '2a' algo and it has a cost factor of 12" => [
            [
                $user = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
            ],
            $user,
            '2a', // expected algorithm
            12, // expected cost factor
        ];
        yield "Test 2 - Test that user's password matches bcrypt pattern, uses '2a' algo and it has a cost factor of 12" => [
            [
                $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
            ],
            $user2,
            '2a', // expected algorithm
            12, // expected cost factor
        ];
    }
}
