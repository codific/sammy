<?php
declare(strict_types=1);

namespace App\Traits\UtilsBundle;

use App\Interface\EntityInterface;
use App\Repository\Abstraction\AbstractRepository;
use App\Util\NamespaceFunctions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait CrudDeleteTrait
{
    /**
     * Delete action.
     */
    public function abstractDelete(Request $request, EntityInterface $entity, bool $force = false): Response
    {
        $entityName = $entity->getEntityName();
        $entityUnderscoreName = $entity->getUnderscoreEntityName();
        $urlNamespace = NamespaceFunctions::getUrlNamespace($request);

        /** @var AbstractRepository $repository */
        // @phpstan-ignore-next-line
        $repository = $this->managerRegistry->getRepository(get_class($entity));
        $repository->delete($entity, $force);

        $this->addFlash(
            'success',
            $this->translator->trans("admin.{$entityUnderscoreName}.delete_success", [$entityUnderscoreName => trim("$entity")])
        );

        $entityDeletedEvent = '\\App\Event\\Admin\\Delete\\'.ucfirst($entity->getEntityName(true)).'DeletedEvent';
        if (class_exists($entityDeletedEvent)) {
            $this->eventDispatcher->dispatch(new $entityDeletedEvent($request, $entity));
        }

        return $this->redirectToRoute($urlNamespace.'_'.strtolower($entityName).'_index', $request->query->all());
    }
}
