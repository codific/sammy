<?php
declare(strict_types=1);

namespace App\Traits\UtilsBundle;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait CrudPopulateEnumerationSelectTrait
{
    /**
     * Ajax function that populates the data for the enumeration types.
     *
     * @throws \ReflectionException
     */
    public function abstractAjaxPopulateEnumerationSelect(string $entityClass, string $name, string $translationDomain = 'admin'): JsonResponse
    {
        $reflection = (new \ReflectionClass($entityClass))->newInstance();
        $entityUnderscoreName = $reflection->getUnderscoreEntityName();
        $propertyUnderscoreName = $reflection->getUnderscoreEntityName($name);

        $results = [];
        // @phpstan-ignore-next-line
        foreach ($entityClass::{'getAll'.ucfirst($name)}() as $key => $value) {
            $results[$key] = $this->translator->trans("{$translationDomain}.{$entityUnderscoreName}.{$propertyUnderscoreName}_enum", ['value' => $key]);
        }

        return new JsonResponse(json_encode($results, JSON_FORCE_OBJECT), Response::HTTP_OK, [], true);
    }
}
