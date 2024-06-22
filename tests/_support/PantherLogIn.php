<?php

declare(strict_types=1);

namespace App\Tests\_support;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/** 
 * NOTE:
 * Waiting for https://github.com/symfony/panther/pull/574 to be merged.
 *
 * There's currrently no way for a user to be logged in
 * without actually filling out the login form so this trait allows us to do just that.
 */
trait PantherLogIn
{
    public function loginUser(UserInterface $user, string $firewallContext = 'main', ?SessionStorageInterface $sessionStorage = null) {
        if (!interface_exists(UserInterface::class)) {
            throw new \LogicException(sprintf('"%s" requires symfony/security-core to be installed.', __METHOD__));
        }

        if (!$user instanceof UserInterface) {
            throw new \LogicException(sprintf('The first argument of "%s" must be instance of "%s", "%s" provided.', __METHOD__, UserInterface::class, get_debug_type($user)));
        }

        // Make a single request to avoid 'Invalid cookie domain' error when attempting to
        // set a cookie to the uninitialised web driver. The path does not matter for this
        $this->client->request('GET', '/');

        if(null === $sessionStorage) {
            $sessionStorage = new MockFileSessionStorage('var/cache/panther/sessions');
        }

        $session = new Session($sessionStorage);
        $token = new UsernamePasswordToken($user, $firewallContext, $user->getRoles());
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    protected function tearDown(): void
    {
        $this->client->getCookieJar()->clear();
        parent::tearDown();
    }
}
