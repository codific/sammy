<?php

declare(strict_types=1);

namespace App\Tests\_support;

use _PHPStan_a540e44a3\Nette\Neon\Exception;
use App\Entity\Abstraction\AbstractEntity;
use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerHelperTrait
{
    protected ?EntityManagerInterface $specialEntityManager = null;
    private array $persistedEntities = [];

    public function beginSpecialTransaction(EntityManagerInterface $entityManager): void
    {
        $this->specialEntityManager = $entityManager;
    }

    public function specialPersist(AbstractEntity $entity): void
    {
        $this->specialEntityManager->persist($entity);
        $this->persistedEntities[] = $entity;
    }

    public function specialFlush(): void
    {
        $this->specialEntityManager->flush();
    }

    public function rollbackSpecialTransaction(): void
    {
        if ($this->specialEntityManager === null) {
            throw new Exception('You need to start a special transaction first in order to rollback one');
        }

        foreach ($this->persistedEntities as $entity) {
            $entity = $this->specialEntityManager->find($entity::class, $entity->getId());

            if ($entity !== null) {
                $this->specialEntityManager->remove($entity);

                if (($key = array_search($entity, $this->persistedEntities, true)) !== false) {
                    unset($this->persistedEntities[$key]);
                }
            }
        }
        $this->specialEntityManager->flush();
    }
}
