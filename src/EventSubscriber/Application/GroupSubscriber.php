<?php

declare(strict_types=1);

namespace App\EventSubscriber\Application;

use App\Entity\Group;
use App\Entity\User;
use App\Event\Admin\Delete\GroupDeletedEvent;
use App\Repository\GroupRepository;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly GroupService $groupService,
        private readonly GroupRepository $groupRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GroupDeletedEvent::class => [
                'onGroupDeleted',
            ],
        ];
    }


    public function onGroupDeleted(GroupDeletedEvent $event)
    {
        $group = $event->getGroup();

        /** @var User $user */
        $user = $this->security->getUser();

        $groupsByParent = $this->groupService->orderGroupByParent();
        $immediateChilds = $this->groupRepository->findBy(["parent" => $group]);
        foreach ($immediateChilds as $immediateChild) {
            $this->groupRepository->trash($immediateChild);
            $this->deleteChildsIfAny($immediateChild, $groupsByParent);
        }

        $this->entityManager->flush();
    }

    private function deleteChildsIfAny(Group $parent, array $groupsByParent): void
    {
        $childs = $groupsByParent[$parent->getId()] ?? [];
        foreach ($childs as $child) {
            $this->groupRepository->trash($child);
            $this->deleteChildsIfAny($child, $groupsByParent);
        }
    }
}