<?php
declare(strict_types=1);

namespace App\Tests\misc;

use App\Tests\_support\AbstractKernelTestCase;
use Symfony\Component\Yaml\Yaml;

class FrameworkConfigTest extends AbstractKernelTestCase
{

    /**
     * @group asvs
     * @dataProvider configFrameworkProvider
     * @testdox ASVS 3.4.1 - $_dataName
     */
    public function testConfigFramework(array $expectedConfigSetting): void
    {
        $configPath = $this->parameterBag->get('kernel.project_dir')."/config/packages/framework.yaml";
        $config = Yaml::parseFile($configPath);

        $sessionConfig = $config['framework']['session'];

        foreach ($expectedConfigSetting as $key => $value) {
            self::assertEquals($value, $sessionConfig[$key]);
        }
    }

    public function configFrameworkProvider(): array
    {
        return [
            "Test 1 - Test that the cookie settings in framework.yaml are set correctly" => [
                [
                    'cookie_secure' => 'auto',
                    'cookie_samesite' => 'lax',
                ],
            ],
        ];
    }
}
