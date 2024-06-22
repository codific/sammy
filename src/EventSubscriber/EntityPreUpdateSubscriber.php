<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\SanitizerService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class EntityPreUpdateSubscriber implements EventSubscriber
{
    public function __construct(private readonly SanitizerService $sanitizeService)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
            Events::prePersist,
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $purifyFields = [];
        $reflection = new \ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            if ($property->getType() instanceof \ReflectionNamedType && $property->getType()->getName() === 'string') {
                $purifyFields[] = $property->getName();
            }
        }
        foreach ($purifyFields as $field) {
            $value = $entity->{'get'.ucfirst($field)}(); /* @phpstan-ignore-line */
            if (!in_array($field, $entity->getLessPurifiedFields(), true)) {
                $sanitizedValue = $this->sanitizeService->sanitizeValue($value);
                $entity->{'set'.ucfirst($field)}($sanitizedValue); /* @phpstan-ignore-line */
            } else {
                $sanitizedValue = $this->sanitizeService->sanitizeValue($value, SanitizerService::LIBERAL);
                $entity->{'set'.ucfirst($field)}($sanitizedValue); /* @phpstan-ignore-line */
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $purifyFields = [];
        $reflection = new \ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            if ($property->getType() instanceof \ReflectionNamedType && $property->getType()->getName() === 'string') {
                $purifyFields[] = $property->getName();
            }
        }
        foreach ($args->getEntityChangeSet() as $fieldName => $values) {
            if (in_array($fieldName, $purifyFields, true)) {
                if (!in_array($fieldName, $entity->getLessPurifiedFields(), true)) {
                    $sanitizedValue = $this->sanitizeService->sanitizeValue($args->getNewValue($fieldName));
                    $args->setNewValue($fieldName, $sanitizedValue);
                } else {
                    $sanitizedValue = $this->sanitizeService->sanitizeValue($args->getNewValue($fieldName), SanitizerService::LIBERAL);
                    $args->setNewValue($fieldName, $sanitizedValue);
                }
            }
        }
    }
}
