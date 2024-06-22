<?php

declare(strict_types=1);

namespace App\Security\Application;

use App\Entity\User;
use App\Event\Application\UserAuthenticatedEvent;
use App\Form\Application\UserLoginType;
use App\Security\Badge\NotCompromisedPasswordBadge;
use App\Service\ConfigurationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly FormFactoryInterface $formFactory,
        private readonly HttpUtils $httpUtils,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConfigurationService $configurationService
    ) {
    }

    public function supports(Request $request): bool
    {
        $supportsLoginForm = $this->configurationService->getBool('login.internal');
        if ($supportsLoginForm) {
            return $this->httpUtils->checkRequestPath($request, 'app_login_login') && $request->isMethod('POST');
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        $userBadge = new UserBadge($credentials->get('email')->getData(), [$this->entityManager->getRepository(User::class), 'findNonAdminUserByEmail']);
        $badges = [];
        $credentialBadge = new PasswordCredentials($credentials->get('password')->getData());
        $badges[] = new NotCompromisedPasswordBadge($credentials->get('password')->getData());

        return new Passport($userBadge, $credentialBadge, $badges);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $currentUser = $token->getUser();
        if ($currentUser instanceof User) {
            $this->eventDispatcher->dispatch(new UserAuthenticatedEvent($request, $currentUser));
        }
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName) ?? '';
        if (strlen($targetPath) !== 0) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login_login');
    }

    public function getCredentials(Request $request): FormInterface
    {
        $form = $this->formFactory->create(UserLoginType::class);
        $form->handleRequest($request);
        if (!($form->isSubmitted() && $form->isValid())) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('application.general.login_invalid_credentials', [], 'application'));
        }
        return $form;
    }
}
