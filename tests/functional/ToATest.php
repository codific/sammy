<?php

namespace App\Tests\functional;

use App\Entity\User;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;
use App\Service\ConfigurationService;

class ToATest extends AbstractWebTestCase
{
    /**
     * @dataProvider testToaProvider
     * @testdox $_dataName
     */
    public function testToa(User $user, bool $isSingleInstance, string|null $expectedURL): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $newConfigurationService = $this->createPartialMock(ConfigurationService::class, ['get', 'getBool']);
        $returnMapGet = array();
        $newConfigurationService
            ->method('get')
            ->willReturnMap($returnMapGet);

        $returnMapGetBool = array(
            array("single.instance", false, $isSingleInstance),
            array("login.register", false, true),
            array("login.internal", false, true),
            array("login.github", false, true),
            array("login.gitlab", false, true),
            array("login.google", false, true),
            array("login.admin", false, true),
            array("login.sso", false, false),
            array("login.vpn", false, false),
        );
        $newConfigurationService
            ->method('getBool')
            ->willReturnMap($returnMapGetBool);

        static::getContainer()->set(ConfigurationService::class, $newConfigurationService);

        $this->client->followRedirects(true);
        $this->client->loginUser($user, 'boardworks');

        $this->client->request("GET", $this->urlGenerator->generate("app_dashboard_index"));

        $iframe = $this->client->getCrawler()->filter('iframe')->first()->getNode(0) ? $this->client->getCrawler()->filter('iframe')->first() : null;

        if ($isSingleInstance === false && $iframe !== null) {
            $iframeURL = $iframe->attr('src');
            self::assertEquals($expectedURL, $iframeURL);
        } else {
            self::assertNull($iframe);
        }
    }

    private function testToaProvider(): array
    {
        $user = (new UserBuilder())->build();

        return [
            "Positive 1 - Test that the right google doc url iframe ToA is loaded when single instance is 0" => [
                $user, // user
                false, // is single instance
                "https://docs.google.com/document/d/e/2PACX-1vRf8fr1MnPgoI1D29JUSz2aJbL6_i6gi1hdNkQ0Gw4xjRB3UOKGxZAR6rZm8wXLrtNNu07d0OAeDoXH/pub?embedded=true", // expected url
            ],
            "Negative 1 - Test that the iframe with the ToA is not present when single instance is 1" => [
                $user, // user
                true, // is single instance
                null, // expected url
            ],
        ];
    }
}