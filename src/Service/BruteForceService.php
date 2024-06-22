<?php
/**
 * This is automatically generated file using the Codific Prototizer.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Abstraction\FailedLogin;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;

class BruteForceService
{
    private Request $request;

    /**
     * Use when retrieving the number of recent failed logins.
     * In minutes.
     */
    private int $timeFrame = 10;

    /**
     * Threshold values
     * Example: for 15 failed logins user will be not allowed to login for next 60 seconds.
     */
    private array $threshold = [
        15 => 60,
        25 => 120,
        35 => 240,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->request = Request::createFromGlobals();
    }

    public function addFailAttempt(string $username = ''): void
    {
        $failedLogin = new FailedLogin();
        $failedLogin->setUsername($username);
        $failedLogin->setIp($this->request->getClientIp());
        $this->entityManager->persist($failedLogin);
        $this->entityManager->flush();
    }

    /**
     * @param string $filter   use IP(Default) or USERNAME or BOTH to choice how to check for login delay
     * @param string $username if you choice USERNAME or BOTH for filter you must supply user name here
     *
     * @throws NonUniqueResultException
     */
    public function getDelay(string $filter = 'IP', string $username = ''): int
    {
        $repoFailedLogin = $this->entityManager->getRepository(FailedLogin::class);
        $queryBuilder = $repoFailedLogin->createQueryBuilder('a');
        $queryBuilder = $queryBuilder->select('count(a.id) as count, max(a.createdAt) as lastDate')
            ->where('a.createdAt > :timeframe')
            ->setParameter('timeframe', new \DateTime("-{$this->timeFrame} minutes"));

        if (strtoupper($filter) === 'IP') {
            $queryBuilder->andWhere('a.ip = :ip')
                ->setParameter('ip', $this->request->getClientIp());
        }
        if (strtoupper($filter) === 'USERNAME') {
            $queryBuilder->andWhere('a.username = :username')
                ->setParameter('username', $username);
        }
        if (strtoupper($filter) === 'BOTH') {
            $queryBuilder->andWhere('a.username = :username AND a.ip = :ip')
                ->setParameter('username', $username)
                ->setParameter('ip', $this->request->getClientIp());
        }

        $result = $queryBuilder->getQuery()->getOneOrNullResult();
        if ($result === null) {
            return 0;
        }

        $failedAttempts = $result['count'];
        $lastFailedTimestamp = $result['lastDate'];

        krsort($this->threshold);
        foreach ($this->threshold as $attempts => $delay) {
            if ($failedAttempts > $attempts && time() < (strtotime($lastFailedTimestamp) + $delay)) {
                return (strtotime($lastFailedTimestamp) + $delay) - time();
            }
        }

        return 0;
    }
}
