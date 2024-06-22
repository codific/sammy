<?php

declare(strict_types=1);

namespace App\Util;

use Doctrine\ORM\QueryBuilder;

final class ViewParameters extends AbstractRepositoryParameters
{
    private array $queryParams = [];

    private ?QueryBuilder $queryBuilder = null;

    private array $filters = [];

    private ?string $searchFormTwigPath = null;

    private array $searchFormTooltipFields = [];

    private array $joins = [];

    private array $order = [];

    /** @var \stdClass|null Any additional properties which have to be sent to the view */
    public ?\stdClass $additionalViewVars = null;

    /**
     * ViewParameters constructor.
     */
    public function __construct()
    {
        $this->additionalViewVars = new \stdClass();
    }

    /**
     * Get the url query params.
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Set additional url query parameters that will be used for creating the paginator.
     *
     * @return $this
     */
    public function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * Get the predefined Query builder.
     */
    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * Override the default query builder by some custom query builder you have in a certain repository
     * e.g., setQueryBuilder($myRepository->getMyCustomQueryBuilder()).
     *
     * @return $this
     */
    public function setQueryBuilder(?QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Get the additional filters.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Set additional filters that will be always used in the query builder to filter a specific subset
     * e.g., setFilters(["type" => MyTypeEnum::TYPE_1]).
     *
     * @return $this
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get the search form override file.
     */
    public function getSearchFormTwigPath(): ?string
    {
        return $this->searchFormTwigPath;
    }

    /**
     * Change the default search form twig from _search_form.html.twig to something else
     * e.g., setSearchFormTwigPath("admin/my_class/_my_search_form.html.twig") to use templates/admin/my_class/_my_search_form.html.twig.
     *
     * @return $this
     */
    public function setSearchFormTwigPath(?string $searchFormTwigPath): self
    {
        $this->searchFormTwigPath = $searchFormTwigPath;

        return $this;
    }

    /**
     * Get the search tooltip fields.
     */
    public function getSearchFormTooltipFields(): array
    {
        return $this->searchFormTooltipFields;
    }

    /**
     * Override the default search form tooltip fields
     * e.g.,
     * setSearchFormTooltipFields("name", "surname");
     * setSearchFormTooltipFields($this->translator->trans('admin.internship.trainers'), $this->translator->trans('admin.internship.organisation'));.
     *
     * @return $this
     */
    public function setSearchFormTooltipFields(string ...$searchFormTooltipFields): self
    {
        $this->searchFormTooltipFields = array_merge($this->searchFormTooltipFields, $searchFormTooltipFields);

        return $this;
    }

    /**
     * Add join and select.
     *
     * @return $this
     */
    public function addJoin(string $with, string $property, string $type = 'inner'): self
    {
        $this->joins[] = ['with' => $with, 'property' => $property, 'type' => $type];

        return $this;
    }

    /**
     * Add order by.
     *
     * @return $this
     */
    public function addOrderBy(string $orderColumn, string $orderDirection): self
    {
        $this->order[] = [$orderColumn, $orderDirection];

        return $this;
    }

    /**
     * Get array with only the modified properties.
     */
    public function getModifiedPropertiesArray(): array
    {
        try {
            foreach (get_object_vars($this->additionalViewVars) as $key => $value) {
                if (!property_exists($this, $key)) {
                    $this->{$key} = $value; // @phpstan-ignore-line
                }
            }
            $diff = $this->arrayRecursiveDiff(
                get_object_vars($this),
                (new \ReflectionClass($this))->getDefaultProperties()
            );
            if (isset($diff['additionalViewVars'])) {
                unset($diff['additionalViewVars']);
            }

            return $diff;
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Recursive diff between two arrays.
     */
    private function arrayRecursiveDiff(array $firstArray, array $secondArray): array
    {
        $result = [];
        foreach ($firstArray as $key => $value) {
            if (array_key_exists($key, $secondArray)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayRecursiveDiff($value, $secondArray[$key]);
                    if (count($recursiveDiff) > 0) {
                        $result[$key] = $recursiveDiff;
                    }
                } else {
                    if ($value !== $secondArray[$key]) {
                        $result[$key] = $value;
                    }
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
