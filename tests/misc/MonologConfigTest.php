<?php
declare(strict_types=1);

namespace App\Tests\misc;

use App\Tests\_support\AbstractKernelTestCase;
use Symfony\Component\Yaml\Yaml;

class MonologConfigTest extends AbstractKernelTestCase
{

    /**
     * @group asvs
     * @testdox ASVS 1.2.3 - Test monolog handlers level is correctly set for production
     */
    public function testMonologConfigHandlersLevel(): void
    {
        $configPath = $this->parameterBag->get('kernel.project_dir')."/config/packages/monolog.yaml";
        $config = Yaml::parseFile($configPath);

        self::assertTrue(isset($config['when@prod']));
        $handlers = $config['when@prod']['monolog']['handlers'];
        self::assertTrue(isset($handlers));
        self::assertTrue(isset($handlers['audit']));
        self::assertTrue(isset($handlers['main']));
        self::assertTrue(isset($handlers['security']));
        self::assertTrue(isset($handlers['nested']));
        self::assertTrue(isset($handlers['console']));

        self::assertEquals("debug", $handlers['audit']['level']);
        self::assertEquals("error", $handlers['main']['level']);
        self::assertEquals("info", $handlers['security']['level']);
        self::assertEquals("debug", $handlers['nested']['level']);
        self::assertNotTrue(isset($handlers['console']['level']));
    }

    /**
     * @group asvs
     * @dataProvider monologConfigForInstanceProvider
     * @testdox ASVS 1.7.1 - $_dataName
     */
    public function testMonologConfigForInstance(string $instance, array $expectedHandlers): void
    {
        $configPath = $this->parameterBag->get('kernel.project_dir')."/config/packages/monolog.yaml";
        $config = Yaml::parseFile($configPath);

        $monologInstance = 'when@'.$instance;

        self::assertTrue(isset($config[$monologInstance]));
        $handlers = $config[$monologInstance]['monolog']['handlers'];

        self::assertTrue(isset($handlers));
        foreach (array_keys($expectedHandlers) as $expectedHandler) {
            self::assertTrue(isset($handlers[$expectedHandler]));

            if (isset($handlers[$expectedHandler]['level'])) {
                self::assertEquals($expectedHandlers[$expectedHandler], $handlers[$expectedHandler]['level']);
            }
            if (isset($handlers[$expectedHandler]['action_level'])) {
                self::assertEquals($expectedHandlers[$expectedHandler], $handlers[$expectedHandler]['action_level']);
            }
        }
    }

    public function monologConfigForInstanceProvider(): \Generator
    {
        yield "Test 1 - Test that monolog config is set for instance dev" => [
            "dev",
            [
                "main" => "debug",
                "console" => "",
            ], // expected handlers and levels
        ];
        yield "Test 2 - Test that monolog config is set for instance prod" => [
            "prod",
            [
                "audit" => "debug",
                "main" => "error",
                "security" => "info",
                "nested" => "debug",
                "console" => "",
            ], // expected handlers and levels
        ];
        yield "Test 3 - Test that monolog config is set for instance test" => [
            "test",
            [
                "audit" => "debug",
                "main" => "error",
                "security" => "info",
                "nested" => "debug",
                "console" => "",
            ], // expected handlers and levels
        ];
        yield "Test 4 - Test that monolog config is set for instance stress" => [
            "stress",
            [
                "audit" => "debug",
                "main" => "error",
                "security" => "info",
                "nested" => "debug",
                "console" => "",
            ], // expected handlers and levels
        ];
    }
}
