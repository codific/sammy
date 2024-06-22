<?php
declare(strict_types=1);

namespace App\Tests\functional;

use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;

class AdministratorControllerTest extends AbstractWebTestCase
{
    public function testSessionCookieHasAtLeast64BitsOfEntropy(): void
    {
        $client = $this->client;
        $client->request('GET', '/login');

        $user = (new UserBuilder())->build();
        $this->loginUser($user, 'boardworks');

        $response = $client->getResponse();

        // Check if the session cookie exists
        $sessionCookie = $client->getCookieJar()->get("MOCKSESSID");

        // Assertions
        self::assertTrue($sessionCookie !== null, 'Session cookie does not exist.');

        $entropy = $this->calculateEntropy($sessionCookie->getValue());
        self::assertGreaterThanOrEqual(128, $entropy);
    }

    private function calculateEntropy(string $data): int
    {
        $stringLength = mb_strlen($data, 'ASCII');
        $totalBits = $stringLength * 7;

        return $totalBits;
    }
}
