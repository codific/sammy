<?php
declare(strict_types=1);

namespace App\Util;

use Doctrine\ORM\QueryBuilder;

final class IndexViewParameters
{
    private ?QueryBuilder $queryBuilder = null;

    private ?RepositoryParameters $repositoryParameters = null;

    private array $viewParameters = [];

    private array $queryParams = [];

    private string $entityName = '';

    private string $entityCamelCaseName = '';

    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(?QueryBuilder $queryBuilder): IndexViewParameters
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    public function getRepositoryParameters(): ?RepositoryParameters
    {
        return $this->repositoryParameters;
    }

    public function setRepositoryParameters(?RepositoryParameters $repositoryParameters): IndexViewParameters
    {
        $this->repositoryParameters = $repositoryParameters;

        return $this;
    }

    public function getViewParameters(): array
    {
        return $this->viewParameters;
    }

    public function setViewParameters(array $viewParameters): IndexViewParameters
    {
        $this->viewParameters = $viewParameters;

        return $this;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function setQueryParams(array $queryParams): IndexViewParameters
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): IndexViewParameters
    {
        $this->entityName = $entityName;

        return $this;
    }

    public function getEntityCamelCaseName(): string
    {
        return $this->entityCamelCaseName;
    }

    public function setEntityCamelCaseName(string $entityCamelCaseName): IndexViewParameters
    {
        $this->entityCamelCaseName = $entityCamelCaseName;

        return $this;
    }
}
