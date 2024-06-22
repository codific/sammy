<?php
declare(strict_types=1);

namespace App\Tests\_support;

use App\Entity\Abstraction\AbstractEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractKernelTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected ParameterBagInterface $parameterBag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->parameterBag = self::getContainer()->get('parameter_bag');
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

    protected function persistEntities(AbstractEntity ...$entitiesToPersist): void
    {
        foreach ($entitiesToPersist as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }

}