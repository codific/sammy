<?php

declare(strict_types=1);

namespace App\EventSubscriber\Application\ToA;

use App\Entity\User;
use App\Event\Admin\Create\ProjectCreatedEvent;
use App\Event\Application\ToaAcceptedEvent;
use App\Repository\UserRepository;
use App\Service\ConfigurationService;
use App\Service\ProjectService;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ToASubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProjectService $projectService,
        private readonly UserRepository $userRepository,
        private readonly ConfigurationService $configurationService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest'],
            ToaAcceptedEvent::class => ['onToaAccepted'],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if ($user === null) {
            return;
        }

        if (!($user instanceof User) || $user->getAgreedToTerms() || $user->isAdmin() || $this->security->isGranted('IS_IMPERSONATOR')) {
            return;
        }

        if ($this->configurationService->getBool('single.instance')) {
            $user->setAgreedToTerms(true);
            $this->entityManager->flush();

            return;
        }

        $request = $event->getRequest();
        $mfaInit = $this->urlGenerator->generate('app_auth_mfa_init');
        $mfaVerify = $this->urlGenerator->generate('app_auth_mfa_verify');
        $mfaBackupCodes = $this->urlGenerator->generate('app_auth_mfa_backup_codes');
        $mfaCheck = $this->urlGenerator->generate('2fa_front_login');
        $toa = $this->urlGenerator->generate('app_dashboard_toa');
        if (!in_array($request->getPathInfo(), [$mfaVerify, $mfaInit, $mfaCheck, $toa, $mfaBackupCodes], true)) {
            $event->setResponse(new RedirectResponse($toa));
        }
    }

    public function onToaAccepted(ToaAcceptedEvent $event)
    {
        $user = $event->getUser();

        $isAlone = count($this->userRepository->findAllExcept($user)) === 0;
        $groupUsers = $user->getGroupUsers();
        if (sizeof($groupUsers) === 1) {
            $group = $groupUsers->first()->getGroup();
            if (sizeof($group->getGroupProjects()) === 0 && $isAlone) {
                $project = $this->projectService->createProject('SAMMY default project '.date('dmY'), 'This is your default scope for SAMMY.', [$group->getId()], Constants::SAMM_ID);
                $this->eventDispatcher->dispatch(new ProjectCreatedEvent(request: null, project: $project));
            }
        }
    }
}
