<?php
declare(strict_types=1);

namespace App\Tests\misc;

use App\Enum\Role;
use App\Tests\_support\AbstractKernelTestCase;
use Symfony\Component\Yaml\Yaml;

class SecurityConfigTest extends AbstractKernelTestCase
{

    /**
     * @group asvs
     * @dataProvider securityConfigIsValidProvider
     * @testdox ASVS 1.2.2 - $_dataName
     */
    public function testSecurityConfigIsValid(string $rulePathToAssert, string $expectedRole): void
    {
        $configPath = $this->parameterBag->get('kernel.project_dir')."/config/packages/security.yaml";
        $config = Yaml::parseFile($configPath);

        self::assertTrue(isset($config['security']['password_hashers']));
        self::assertTrue(isset($config['security']['providers']));
        self::assertTrue(isset($config['security']['firewalls']));
        self::assertTrue(isset($config['security']['access_control']));

        $accessRule = array_filter($config['security']['access_control'], static function ($rule) use ($rulePathToAssert) {
            return isset($rule['path']) && $rule['path'] === $rulePathToAssert;
        });

        self::assertNotEmpty($accessRule);
        self::assertEquals($expectedRole, $accessRule[key($accessRule)]['roles']);
    }

    public function securityConfigIsValidProvider(): \Generator
    {
        yield "Test 1 - Test that any endpoint requires an authenticated user in security.yaml" => [
            '^/',
            Role::USER->string(),
        ];
    }

}
