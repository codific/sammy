<?php

namespace App\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityManagerTestCase extends KernelTestCase
{
    protected ?EntityManager $entityManager;

    protected function setUp(): void
    {

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->beginTransaction();

    }

    protected function tearDown(): void
    {

        $this->entityManager->rollback();

        $this->entityManager->close();
        $this->entityManager = null;

        parent::tearDown();

    }

    protected function getLastObjectOfClass($class)
    {
        $repo = $this->entityManager->getRepository($class);
        $allResults = $repo->findAll();

        return end($allResults);
    }

    protected function getObjectOfClass($class, int $counter)
    {
        $allResults = $this->entityManager->getRepository($class)->findAll();
        return $allResults[$counter];
    }


}