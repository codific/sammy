<?php

declare(strict_types=1);

namespace App\Security\Application;

use App\Entity\User;
use App\Event\Application\UserAuthenticatedEvent;
use App\Form\Application\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

class PasswordResetHashAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormFactoryInterface $formFactory,
        private readonly HttpUtils $httpUtils,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function supports(Request $request): bool
    {
        $form = $this->formFactory->create(ResetPasswordType::class);
        $form->handleRequest($request);

        return $this->httpUtils->checkRequestPath($request, 'app_login_password-reset-hash') && $request->isMethod('POST') && $form->isSubmitted() && $form->isValid();
    }

    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(ResetPasswordType::class);
        $form->handleRequest($request);
        $hash = $request->get('hash');

        $userBadge = new UserBadge($hash, [$this->entityManager->getRepository(User::class), 'loadUserByPasswordResetHash']);

        $user = $userBadge->getUser();
        if ($user instanceof User) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('newPassword')->getData()));
            $user->setPasswordResetHash('');
            $user->setPasswordResetHashExpiration(null);
            $this->entityManager->flush();
        }

        return new SelfValidatingPassport($userBadge);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->eventDispatcher->dispatch(new UserAuthenticatedEvent($request, $user));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login_login');
    }
}
