<?php

namespace App\Tests\functional;


use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;


class MfaControllerTest extends AbstractWebTestCase
{
    /**
     * @group asvs
     * @group security
     * @testdox ASVS 8.2.1 - $_dataName
     */
    public function testForAntiCachingHeadersAtMfaBackup(): void
    {
        $email = bin2hex(random_bytes(5));
        $testUser = (new UserBuilder($this->entityManager))->withEmail($email."@test.test")->build();

        $this->client->loginUser($testUser, "boardworks");
        $this->client->request("GET", "/auth/mfa/backup");

        $headerCacheControl = array_map(
            'trim',
            explode(",", $this->client->getResponse()->headers->get("Cache-Control"))
        );

        self::assertContains("must-revalidate", $headerCacheControl);
        self::assertContains("no-store", $headerCacheControl);
        self::assertContains("private", $headerCacheControl);
        self::assertContains("max-age=0", $headerCacheControl);
    }
}