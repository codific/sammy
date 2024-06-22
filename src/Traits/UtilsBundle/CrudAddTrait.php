<?php
declare(strict_types=1);

namespace App\Traits\UtilsBundle;

use App\Entity\Abstraction\AbstractEntity;
use App\Util\FormParameters;
use App\Util\NamespaceFunctions;
use App\Util\ViewParameters;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait CrudAddTrait
{
    /**
     * Add new action.
     *
     * @param class-string|null $tenantInterface
     *
     * @throws ORMException
     */
    public function abstractAdd(
        Request $request,
        string $entityClassName,
        string $formClassName,
        FormParameters $formParameters,
        ViewParameters $viewParameters,
        ?string $tenantInterface = null,
        bool $tenantRequired = true
    ): Response {
        /** @var AbstractEntity $entity */
        $entity = new $entityClassName();
        $entityName = $entity->getEntityName();
        $entityDisplayName = $entity->getEntityName();
        $entityUnderscoreName = $entity->getUnderscoreEntityName();
        $viewParameters = $viewParameters->getModifiedPropertiesArray();
        $urlNamespace = NamespaceFunctions::getUrlNamespace($request);
        $templateNamespace = NamespaceFunctions::getTemplateNamespace($request);

        $queryParams = (isset($viewParameters['queryParams'])) ? $viewParameters['queryParams'] : $request->query->all(
        );

        foreach ($entity->getParentClasses() as $parent) {
            if ($request->query->has($parent)) {
                /** @var EntityManager $entityManager */
                $entityManager = $this->managerRegistry->getManager();
                // @phpstan-ignore-next-line
                $entity->{'set'.ucfirst($parent)}(
                    $entityManager->getReference('\\App\Entity\\'.ucfirst($parent), $request->query->get($parent))
                );
            }
        }
        if ($tenantInterface !== null && $entity instanceof $tenantInterface) {
            $tenant = $this->getTenant(); // @phpstan-ignore-line
            if ($tenant === null && $tenantRequired) {
                $this->addFlash('error', $this->translator->trans('admin.general.tenant_not_selected'));

                return $this->redirectToRoute($urlNamespace.'_'.$entityName.'_index', $request->query->all());
            }
            $entity->setTenant($tenant); // @phpstan-ignore-line
        }

        $form = $this->createForm($formClassName, $entity, $formParameters->getModifiedPropertiesArray());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->handleFileUpload($form, $entity);
            $entityManager = $this->managerRegistry->getManager();
            $entityManager->persist($entity);
            $entityManager->flush();
            $this->addFlash(
                'success',
                $this->translator->trans(
                    "admin.{$entityUnderscoreName}.save_success",
                    [$entityUnderscoreName => trim("$entity")]
                )
            );

            $entityCreatedEvent = '\\App\Event\\Admin\\Create\\'.ucfirst($entity->getEntityName(true)).'CreatedEvent';
            if (class_exists($entityCreatedEvent)) {
                $this->eventDispatcher->dispatch(new $entityCreatedEvent($request, $entity));
            }

            return $this->redirectToRoute($urlNamespace.'_'.$entityName.'_index', $request->query->all());
        }

        $viewVars = [
            $entityDisplayName => $entity,
            'entityName' => $entityName,
            'addForm' => $form->createView(),
            'queryParams' => $queryParams,
        ];

        return $this->render(
            $templateNamespace.'/'.$entityName.'/add.html.twig',
            array_replace_recursive($viewVars, $viewParameters),
        );
    }
}
