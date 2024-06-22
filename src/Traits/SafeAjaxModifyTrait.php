<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Abstraction\UserModifiableFieldsInterface;
use App\Interface\EntityInterface;
use App\Traits\UtilsBundle\CrudAjaxModifyTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait SafeAjaxModifyTrait
{
    use CrudAjaxModifyTrait;

    public function safeAjaxModify(Request $request, EntityInterface&UserModifiableFieldsInterface $entity, ?array $topLevelWhitelist = null): JsonResponse
    {
        $name = $request->request->get('name');

        $safeFields = array_intersect($entity->getModifiableFields(), $entity->getUserModifiableFields());
        $safeFields = ($topLevelWhitelist !== null) ? array_intersect($safeFields, $topLevelWhitelist) : $safeFields;

        return (in_array($name, $safeFields, true)) ? $this->abstractAjaxModify($request, $entity) :
            new JsonResponse(['status' => 'error', 'msg' => 'The field is readonly!'], Response::HTTP_BAD_REQUEST);
    }
}
