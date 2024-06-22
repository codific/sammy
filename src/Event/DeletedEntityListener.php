<?php

declare(strict_types=1);

namespace App\Event;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class DeletedEntityListener
{
    private EntityManagerInterface $entityManager;

    /**
     * DeletedEntityListener constructor.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $archived = (bool) $event->getRequest()->query->get('archived', '');
        if ($archived) {
            return;
        }
        $filter = $this->entityManager->getFilters()->enable('deleted_entity');
        $filter->setParameter('deleted', false);
    }
}
