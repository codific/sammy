<?php

declare(strict_types=1);

namespace App\Twig;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleTotpFactory;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TotpCode extends AbstractExtension
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly KernelInterface $kernel,
        private readonly GoogleTotpFactory $googleTotpFactory,
        private readonly array $noMfaEnvironments
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getTotpCode', $this->getTotpCode(...)),
        ];
    }

    public function getTotpCode(): string
    {
        if (in_array($this->kernel->getEnvironment(), $this->noMfaEnvironments, true) === false) {
            return '';
        }
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return '';
        }

        /** @var TwoFactorInterface $user */
        $user = $token->getUser(); // @phpstan-ignore-line

        return $this->googleTotpFactory->createTotpForUser($user)->now();
    }
}
