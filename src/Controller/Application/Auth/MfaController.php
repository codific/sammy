<?php

declare(strict_types=1);

namespace App\Controller\Application\Auth;

use App\Controller\Application\AbstractController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class MfaController extends AbstractController
{
    /**
     * @Route("/auth/mfa/init", name="auth_mfa_init", methods={"GET"})
     */
    public function initMfa(GoogleAuthenticatorInterface $googleAuthenticatorService, SessionInterface $session): Response
    {
        $user = $this->getUser();
        if ($user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        $secret = $session->get('_auth.mfa.secret', null);
        if ($secret === null) {
            $secret = $googleAuthenticatorService->generateSecret();
            $session->set('_auth.mfa.secret', $secret);
        }
        $user->setSecretKey($secret);
        $qrCode = $googleAuthenticatorService->getQRContent($user);
        $user->setSecretKey('');

        $response = $this->render(
            'application/auth/initMfa.html.twig',
            [
                'qrcode' => $qrCode,
            ]
        );
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /**
     * @Route("/auth/mfa/verify", name="auth_mfa_verify", methods={"POST"})
     *
     * @throws \Exception
     */
    public function verifyMfa(
        Request $request,
        GoogleAuthenticatorInterface $googleAuthenticatorService,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): RedirectResponse {
        $secret = $session->get('_auth.mfa.secret', null);
        $pin = $request->get('pin', '000000');
        if ($secret === null || $pin === '000000' || $pin === '') {
            // missing code or secret key!
            $this->addFlash('danger', $translator->trans('application.general.mfa_missing_code', [], 'application'));

            return $this->redirectToRoute('app_auth_mfa_init');
        }

        $session->remove('_auth.mfa.secret');

        $user = $this->getUser();
        $user->setSecretKey($secret);

        if (!$googleAuthenticatorService->checkCode($user, $pin)) {
            $user->setSecretKey('');
            $this->addFlash('warning', $translator->trans('application.general.mfa_invalid_code', [], 'application'));

            return $this->redirectToRoute('app_auth_mfa_init');
        }

        $this->addFlash('success', $translator->trans('application.general.mfa_success', [], 'application'));
        $this->initBackupCodes($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_auth_mfa_backup_codes');
    }

    /**
     * @Route("/auth/mfa/backup", name="auth_mfa_backup_codes", methods={"GET"})
     */
    public function backupCodes(): Response
    {
        $response = $this->render(
            'application/auth/backup.html.twig'
        );
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /**
     * @Route("/auth/mfa/reset", name="auth_mfa_reset", methods={"POST"})
     */
    public function resetDevice(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        if (!$this->isCsrfTokenValid("{$user->getId()}", $request->request->get('_token'))) {
            return $this->redirectToRoute('app_auth_mfa_backup_codes');
        }
        $user->setSecretKey('');
        $entityManager->flush();

        return $this->redirectToRoute('app_auth_mfa_init');
    }

    /**
     * @throws \Exception
     */
    private function initBackupCodes(User $user)
    {
        if (sizeof($user->getBackupCodes()) !== 0) {
            return;
        }

        $codes = [];
        for ($i = 0; $i < 10; ++$i) {
            $codes[] = Base32::encodeUnpadded(random_bytes(8));
        }

        $user->setBackupCodes($codes);
    }
}
