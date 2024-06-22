<?php

declare(strict_types=1);

namespace App\Util;

abstract class AbstractRepositoryParameters
{
    public static string $defaultOrderColumn = 'id';

    public static string $defaultOrderDirection = 'DESC';

    protected ?string $filter = null;

    protected ?int $page = null;

    protected ?int $pageSize = null;

    protected bool $showDeleted = false;

    protected array $additionalSearchFields = [];

    protected array $orderBy = [];

    /**
     * Get the search field filter.
     */
    public function getFilter(): ?string
    {
        return $this->filter;
    }

    /**
     * Set the search field filter.
     *
     * @return $this
     */
    public function setFilter(?string $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get the current paginator page.
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * Set the current paginator page.
     *
     * @return $this
     */
    public function setPage(?int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the pagination page size.
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * Set the pagination page size.
     *
     * @return $this
     */
    public function setPageSize(?int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * Get if the deleted entities should be shown.
     */
    public function getShowDeleted(): bool
    {
        return $this->showDeleted;
    }

    /**
     * Set if the deleted entities should be shown.
     *
     * @return $this
     */
    public function setShowDeleted(bool $showDeleted): self
    {
        $this->showDeleted = $showDeleted;

        return $this;
    }

    public function getAdditionalSearchFields(): array
    {
        return $this->additionalSearchFields;
    }

    /**
     * @return $this
     */
    public function setAdditionalSearchFields(string ...$names): self
    {
        $this->additionalSearchFields = array_merge($this->additionalSearchFields, $names);

        return $this;
    }
}
