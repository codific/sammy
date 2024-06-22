<?php

declare(strict_types=1);

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class Entity extends AbstractExtension
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getTests(): array
    {
        return [
            new TwigTest('user', $this->user(...)),
            new TwigTest('admin', $this->admin(...)),
            new TwigTest('instanceof', $this->isInstance(...)),
            new TwigTest('entityExists', $this->entityExists(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('callStaticMethod', $this->callStaticMethod(...), ['deprecated' => true, 'alternative' => 'Use `constant("ClassName::Function()")`']),
        ];
    }

    public function user($entity): bool
    {
        $fullClassName = '\\App\Entity\\User';

        return $entity instanceof $fullClassName;
    }

    public function admin($entity): bool
    {
        $fullClassName = '\\App\Entity\\Administrator';

        return $entity instanceof $fullClassName;
    }

    public function isInstance($entity, string $className): bool
    {
        $fullClassName = "\\App\Entity\\$className";

        return $entity instanceof $fullClassName;
    }

    /**
     * The includeDeleted parameter enables finding deleted entities usage: <entity> is entityExists(true).
     */
    public function entityExists($entity = null, bool $includeDeleted = false): bool
    {
        if ($entity instanceof \Doctrine\Common\Proxy\Proxy) {
            if ($includeDeleted && $this->entityManager->getFilters()->isEnabled('deleted_entity')) {
                $this->entityManager->getFilters()->disable('deleted_entity');
            }

            try {
                $entity->__load();
            } catch (EntityNotFoundException $e) {
                $entity = null;
            }

            if ($includeDeleted && !$this->entityManager->getFilters()->isEnabled('deleted_entity')) {
                $this->entityManager->getFilters()->enable('deleted_entity');
            }
        }

        return isset($entity);
    }

    /**
     * @deprecated Use `constant("ClassName::Function()")`
     */
    public function callStaticMethod(string $className, string $propertyName): mixed
    {
        $class = "\\App\Entity\\$className";

        return $class::{"getAll$propertyName"}(); // @phpstan-ignore-line
    }
}
