<?php
declare(strict_types=1);

namespace App\Tests\_support;


use App\Entity\Abstraction\AbstractEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    private SessionInterface $session;
    protected EntityManagerInterface $entityManager;
    protected UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->client->catchExceptions(false);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

    /**
     * This replicates static::createClient()->loginUser()
     * Tracking an internal $session object that can be updated as needed.
     */
    protected function loginUser(UserInterface $user, string $firewallContext): static
    {
        $token = new TestBrowserToken($user->getRoles(), $user, $firewallContext);
        // @deprecated since Symfony 5.4
        if (method_exists($token, 'setAuthenticated')) {
            $token->setAuthenticated(true, false);
        }
        $container = static::getContainer();
        $container->get('security.untracked_token_storage')->setToken($token);

        // Create a new session object
        $this->session = $container->get('session.factory')->createSession();
        // Sets the token in the session
        $this->setLoginSessionValue('_security_'.$firewallContext, serialize($token));

        $domains = array_unique(
            array_map(function (Cookie $cookie) {
                return $cookie->getName() === $this->session->getName() ? $cookie->getDomain() : '';
            }, $this->client->getCookieJar()->all())
        ) ?: [''];

        // For each unique domain name, creates a new cookie with the session name and id and the current domain
        foreach ($domains as $domain) {
            $cookie = new Cookie($this->session->getName(), $this->session->getId(), null, null, $domain);
            $this->client->getCookieJar()->set($cookie);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    protected function setLoginSessionValue(string $name, mixed $value): void
    {
        if (isset($this->session)) {
            $this->session->set($name, $value);
            $this->session->save();
        } else {
            throw new \LogicException("loginUser() must be called instead of client->loginUser() in order to set value in the session");
        }
    }

    /**
     * Sets in the current session and returns a CSRF generated token
     */
    protected function getToken(string $tokenId): string
    {
        $csrfToken = static::getContainer()->get('security.csrf.token_generator')->generateToken();
        $this->setLoginSessionValue(SessionTokenStorage::SESSION_NAMESPACE."/$tokenId", $csrfToken);

        return $csrfToken;
    }

    protected function persistEntities(AbstractEntity ...$entitiesToPersist): void
    {
        foreach ($entitiesToPersist as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }

}