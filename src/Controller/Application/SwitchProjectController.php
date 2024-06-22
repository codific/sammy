<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Project;
use App\Service\ProjectService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SwitchProjectController extends AbstractController
{
    #[Route('/switch/project/{id}', name: 'switch_project', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('PROJECT_ACCESS', 'project')]
    public function switchProject(Request $request, Project $project, ProjectService $projectService): RedirectResponse
    {
        if ($this->isCsrfTokenValid('switch_project', $request->request->get('token'))) {
            $projectService->setCurrentProject($project);
        }
        $this->addFlash('success', $this->trans('application.project.switch_success', ['projectName' => $project->getName()], 'application'));

        return $this->redirectToRoute('app_dashboard_index');
    }
}
