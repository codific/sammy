<?php

declare(strict_types=1);

namespace App\ORM;

use App\Interface\EntityInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class DeletedEntityFilter extends SQLFilter
{
    /**
     * @param string $targetTableAlias
     *
     * @return string
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!$targetEntity->reflClass->implementsInterface(EntityInterface::class)) {
            return '';
        }

        $showDeleted = false;
        if ($this->hasParameter('deleted')) {
            $showDeleted = (bool) str_ireplace("'", '', $this->getParameter('deleted'));
        }

        if ($showDeleted) {
            return sprintf('%s.deleted_at IS NOT NULL', $targetTableAlias);
        }

        return sprintf('%s.deleted_at IS NULL', $targetTableAlias);
    }
}
