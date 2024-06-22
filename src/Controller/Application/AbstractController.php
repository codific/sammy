<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\User;
use App\Service\SanitizerService;
use App\Service\UploadService;
use App\Service\WhitelistUrlProvider;
use App\Translator\Translator;
use App\Utils\RouteValidator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractController extends SymfonyAbstractController
{
    public function __construct(
        protected RequestStack $requestStack,
        protected LoggerInterface $logger,
        protected TranslatorInterface $translator,
        protected ValidatorInterface $validator,
        protected EventDispatcherInterface $eventDispatcher,
        protected UploadService $uploadService,
        protected Translator $customTranslator,
        protected ManagerRegistry $managerRegistry,
        protected SanitizerService $sanitizer,
        protected WhitelistUrlProvider $whitelistUrlProvider,
        protected UrlMatcherInterface $router,
    ) {
        $customTranslator->setDefaultDomain('application');
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Clears flash messages to the current session.
     *
     * @throws \LogicException
     */
    protected function clearFlash(): void
    {
        if (!$this->container->has('session')) {
            throw new \LogicException('You can not use the clearFlash method if sessions are disabled. Enable them in "config/packages/framework.yaml".');
        }

        $this->container->get('session')->getFlashBag()->clear();
    }

    protected function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * We use this function to make sure phpstan doesn't complain about user being userinterface
     * Moreover, if it's not, this method will throw an exception.
     */
    public function getUser(): ?User
    {
        /** @var ?User $user */
        $user = parent::getUser();

        return $user;
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): Form
    {
        $form = parent::createForm($type, $data, $options);
        assert($form instanceof Form);

        return $form;
    }

    protected function addFlash(string $type, mixed $message, bool $escapeHtml = false): void
    {
        parent::addFlash($type, ($escapeHtml) ? $this->sanitizer->sanitizeHtmlChars($message) : $message);
    }

    public function safeRedirect(Request $request, string $fallbackRoute, ?string $url = null, array $attributes = [], int $status = 302): RedirectResponse
    {
        if ($url === '' || $url === null) {
            $url = $request->headers->get('referer');
        }

        $whitelist = $this->whitelistUrlProvider->getWhitelistedUrls();

        if (RouteValidator::validateRoute($request, $this->router, $url, whitelist: $whitelist)) {
            return $this->redirect($url, $status); // @phpstan-ignore-line
        }

        return $this->redirectToRoute($fallbackRoute, $attributes, $status);
    }

}
