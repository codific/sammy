<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Service\ConfigurationService;
use App\Service\Processing\AssessmentExporterService;
use App\Service\PublicAssessmentService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function home(ConfigurationService $configurationService): Response
    {
        // if user is already logged in, don't display the login page again
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        return $this->redirectToRoute('app_login_login');
    }

    /**
     * A function which is just hit by the javascript (public/front/js/session.js) to check behind the scenes
     * if the user is already logged out and notify him that he is looking at expired data
     */
    #[Route(path: '/ping', name: 'session-timeout')]
    public function ping(): JsonResponse
    {
        return new JsonResponse('ok', Response::HTTP_OK);
    }

    #[Route('/timezone', name: 'timezone', methods: ['POST'])]
    public function timeZone(Request $request, UserService $userService): JsonResponse
    {
        $currentUser = $this->getUser();

        $userService->setUserTimeZone($currentUser, $request->request->get('timezone'));

        return new JsonResponse('', Response::HTTP_OK);
    }
}
