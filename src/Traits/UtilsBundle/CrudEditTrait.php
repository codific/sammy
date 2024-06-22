<?php
declare(strict_types=1);

namespace App\Traits\UtilsBundle;

use App\Entity\Abstraction\AbstractEntity;
use App\Interface\EntityInterface;
use App\Util\FormParameters;
use App\Util\NamespaceFunctions;
use App\Util\ViewParameters;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait CrudEditTrait
{
    /**
     * Modify action.
     *
     * @throws ORMException
     */
    public function abstractEdit(
        Request $request,
        AbstractEntity $entity,
        string $formClassName,
        FormParameters $formParameters,
        ViewParameters $viewParameters
    ): Response {
        $entityName = $entity->getEntityName();
        $entityDisplayName = $entity->getEntityName(true);
        $entityUnderscoreName = $entity->getUnderscoreEntityName();
        $viewParameters = $viewParameters->getModifiedPropertiesArray();
        $queryParams = (isset($viewParameters['queryParams'])) ? $viewParameters['queryParams'] : $request->query->all();
        $urlNamespace = NamespaceFunctions::getUrlNamespace($request);
        $templateNamespace = NamespaceFunctions::getTemplateNamespace($request);

        foreach ($entity->getParentClasses() as $parent) {
            if ($request->query->has($parent)) {
                /** @var EntityManager $entityManager */
                $entityManager = $this->managerRegistry->getManager();
                // @phpstan-ignore-next-line
                $entity->{'set'.ucfirst($parent)}($entityManager->getReference('\\App\Entity\\'.ucfirst($parent), $request->query->get($parent)));
            }
        }

        $form = $this->createForm($formClassName, $entity, $formParameters->getModifiedPropertiesArray());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->handleFileUpload($form, $entity);
            $this->managerRegistry->getManager()->flush();
            $this->addFlash(
                'success',
                $this->translator->trans("admin.{$entityUnderscoreName}.save_success", [$entityUnderscoreName => trim("$entity")])
            );

            $entityUpdatedEvent = '\\App\Event\\Admin\\Update\\'.ucfirst($entity->getEntityName(true)).'UpdatedEvent';
            if (class_exists($entityUpdatedEvent)) {
                $this->eventDispatcher->dispatch(new $entityUpdatedEvent($request, $entity));
            }

            return $this->redirectToRoute($urlNamespace.'_'.$entityName.'_index', $request->query->all());
        }

        $viewVars = [
            $entityDisplayName => $entity,
            'entityName' => $entityName,
            'editForm' => $form->createView(),
            'queryParams' => $queryParams,
        ];

        return $this->render($templateNamespace.'/'.$entityName.'/edit.html.twig', array_replace_recursive($viewVars, $viewParameters));
    }
}
