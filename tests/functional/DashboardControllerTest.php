<?php
declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\User;
use App\Enum\Role;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;
use Symfony\Component\HttpFoundation\Request;

class DashboardControllerTest extends AbstractWebTestCase
{
    /**
     * @group asvs
     * @dataProvider contentTypeHeaderProvider
     * @testdox ASVS 14.4.1 - $_dataName
     */
    public function testContentTypeHeader(array $entitiesToPersist, User $user, string $requestMethod, string $route): void
    {
        $this->persistEntities(...$entitiesToPersist);

        $this->client->loginUser($user, "boardworks");

        $this->client->request($requestMethod, $this->urlGenerator->generate($route));

        $contentTypeHeader = $this->client->getResponse()->headers->get("Content-Type");
        self::assertNotNull($contentTypeHeader);
        self::assertStringContainsString('charset=', $contentTypeHeader);
    }

    public function contentTypeHeaderProvider(): \Generator
    {
        yield "Test 1 - Test that Content-Type header is present and it has charset set at app_dashboard_index" => [
            [
                $user = (new UserBuilder())->build(),
            ],
            $user,
            Request::METHOD_GET,
            'app_dashboard_index',
        ];
        yield "Test 2 - Test that Content-Type header is present and it has charset set at app_user_index" => [
            [
                $user = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build(),
            ],
            $user,
            Request::METHOD_GET,
            'app_user_index',
        ];
    }
}
