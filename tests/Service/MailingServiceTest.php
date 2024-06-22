<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Mailing;
use App\Entity\MailTemplate;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\MailingRepository;
use App\Repository\MailTemplateRepository;
use App\Service\MailingService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\EntityManagerTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailingServiceTest extends AbstractKernelTestCase
{
    private MailingService $mailingService;
    private MailingRepository $mailingRepository;

    public function setUp(): void
    {
        $this->mailingService = $this->getContainer()->get(MailingService::class);
        $this->mailingRepository = $this->getContainer()->get(MailingRepository::class);
        parent::setUp();
    }

    public function testAdd()
    {
        ($user = new User())->setEmail($email = rand(300000, 40000000).'@codific.com');
        $this->mailingService->add(\App\Enum\MailTemplateType::USER_WELCOME, $user);
        $mailings = $this->mailingRepository->findBy(['email' => $email]);
        self::assertCount(1, $mailings);
        self::assertCount(
            1,
            $this->entityManager->getRepository(MailTemplate::class)->findBy(['type' => \App\Enum\MailTemplateType::USER_WELCOME])
        );
    }

    public function testAddCustom()
    {
        $mailTemplate = (new MailTemplate())->setType(\App\Enum\MailTemplateType::ADMIN_PASSWORD_RESET)->setMessage(
            'message with custom placeholders [pl]'
        );
        ($admin = new User())->setRoles([Role::ADMINISTRATOR->string()]);
        $this->mailingService->addCustom($admin, $mailTemplate, ['pl' => 'Codific']);
        $mailings = $this->mailingRepository->findBy(['user' => $admin]);
        self::assertCount(1, $mailings);
        self::assertStringContainsString('Codific', current($mailings)->getMessage());
    }

    public function testAddCustomWithNameAndEmail()
    {
        $this->mailingService->addCustomWithNameAndEmail('subject', 'message', $email = rand(300000, 40000000).'@codific.com', 'name', null);
        $mailings = $this->mailingRepository->findBy(['email' => $email]);
        self::assertCount(1, $mailings);
    }

    /**
     * @dataProvider processMailingProvider
     */
    public function testProcessMailing(array $mailings, array $expectedStatuses)
    {
        $mailingServiceMock = $this->getMockBuilder(MailingService::class)->setConstructorArgs([
            $this->entityManager,
            self::getContainer()->get(UrlGeneratorInterface::class),
            self::getContainer()->get(ParameterBagInterface::class),
            self::getContainer()->get(LoggerInterface::class),
            self::getContainer()->get(Filesystem::class),
            self::getContainer()->get(MailTemplateRepository::class),
        ])->onlyMethods(['sendMail'])->getMock();
        $mailingServiceMock->expects(self::any())->method('sendMail')->will(self::returnValue(true));

        // Delete all mailings first, so they won't interfere with the test
        $this->entityManager->createQueryBuilder()->delete()->from(Mailing::class, 'mailing')->where('mailing.id IS NOT NULL')->getQuery()->execute();

        foreach ($mailings as $mailing) {
            $this->entityManager->persist($mailing);
        }
        $this->entityManager->flush();
        $mailingServiceMock->processMailing();
        $counter = 0;
        foreach ($mailings as $mailing) {
            $this->entityManager->refresh($mailing);
            self::assertEquals($expectedStatuses[$counter++], $mailing->getStatus());
        }
    }

    public function processMailingProvider(): array
    {
        return [
            '2 valid mailings' => [
                [(new Mailing())->setEmail(rand(1, 20000).'valid1@codific.com'), (new Mailing())->setEmail(rand(20000, 40000000).'valid2@codific.com')],
                [\App\Enum\MailingStatus::SENT, \App\Enum\MailingStatus::SENT],
            ],
            '1 valid, 1 invalid mail' => [
                [(new Mailing())->setEmail(rand(1, 20000).'valid1@codific.com'), (new Mailing())->setEmail(rand(20000, 40000000).'invalid')],
                [\App\Enum\MailingStatus::SENT, \App\Enum\MailingStatus::FAILED],
            ],
        ];
    }
}
