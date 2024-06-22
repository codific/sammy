<?php

declare(strict_types=1);

namespace App\Util;

final class RepositoryParameters extends AbstractRepositoryParameters
{
    /**
     * Add order by.
     *
     * @param string[][] $orderBy Must be in format [[$orderColum, $orderDirection], [$orderColum, $orderDirection]]
     *
     * @return $this
     */
    public function setOrderBy(array $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Get order by.
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }
}
