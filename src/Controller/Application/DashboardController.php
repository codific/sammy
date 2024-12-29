<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentRepository;
use App\Service\AssessmentStreamService;
use App\Service\AssignmentService;
use App\Service\ProjectService;
use App\Service\ScoreService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController.
 */
#[Route('/dashboard', name: 'dashboard_')]
class DashboardController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     */
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        AssessmentStreamService $streamService,
        ProjectService $projectService,
        AssessmentRepository $assessmentRepository,
        AssignmentService $assignmentService,
        ScoreService $scoreService,
        AssessmentAnswerRepository $assessmentAnswerRepository
    ): Response {
        $currentUser = $this->getUser();

        $currentProject = $projectService->getCurrentProject();
        if ($currentProject !== null) {
            $this->denyAccessUnlessGranted('PROJECT_ACCESS', $currentProject);
            $currentAssessment = $assessmentRepository->findByProjectOptimized($currentProject);
        }

        if ($currentProject === null || $currentAssessment === null) {
            $viewVars = [
                'assessment' => null,
                'evaluationTrackStreams' => [],
                'validationTrackStreams' => [],
                'improvementTrackStreams' => [],
                'businessFunctionScore' => [],
                'securityPracticeScore' => [],
            ];
        } else {
            if ($currentProject->getTemplate()) {
                return $this->redirectToRoute("app_model_showPractice");
            }
            $assignedTo = ($request->cookies->has('assigned') && $request->cookies->get('assigned') === 'true') ? $currentUser : null;

            $streamWeights = [];
            if ($request->cookies->get("unvalidated-score-toggle") === "true") {
                $streamWeights = $scoreService->getActiveStreamWeights($currentAssessment);
            } else {
                $streamWeights = $scoreService->getValidatedStreamWeights($currentAssessment);
            }

            $improvementTrackStreams = $streamService->getStreamsInOrForImprovement($currentAssessment, $assignedTo, $streamWeights);
            $viewVars = [
                'assessment' => $currentAssessment,
                'currentProject' => $currentProject,
                'assignments' => $assignmentService->getAssessmentStreamCurrentAssignments($currentAssessment->getAssessmentAssessmentStreams()->toArray()),
                'evaluationTrackStreams' => $streamService->getEvaluationStreams($currentAssessment, $assignedTo, $streamWeights),
                'validationTrackStreams' => $streamService->getNonVerifiedAnswers($currentAssessment, $assignedTo, $streamWeights),
                'improvementTrackStreams' => $improvementTrackStreams,
                'completedTrackStreams' => $streamService->getCompletedStreams($currentAssessment, $streamWeights),
                'streamWeights' => $streamWeights,
                'phase1Stages' => $assessmentAnswerRepository->findAnswersByStageIndexedByStages($streamService->getStreamsCurrentStages($improvementTrackStreams)),
            ];
        }

        return $this->render('application/index/index.html.twig', $viewVars);
    }

    /**
     * THIS IS NOT UNUSED, DO NOT DELETE.
     */
    public function loadProjects(
        ProjectService $projectService
    ): Response {
        $currentUser = $this->getUser();

        return $this->render(
            'application/partials/_projects.html.twig',
            [
                'projects' => $projectService->getAvailableProjectsForUser($currentUser),
            ]
        );
    }

    public function version(KernelInterface $kernel, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        $popup = false;
        $changes = '';
        if (file_exists($kernel->getProjectDir().'/public/front/CHANGELOG.md')) {
            $changes = file_get_contents($kernel->getProjectDir().'/public/front/CHANGELOG.md');
        }
        $version = substr($changes, strpos($changes, 'v') + 1, strpos($changes, "\n") - 3);
        if ($currentUser !== null) {
            $popup = ($currentUser->getLastChangelog() !== $version && $currentUser->getAgreedToTerms());
            if ($popup) {
                $currentUser->setLastChangelog($version);
                $entityManager->flush();
            }
        }

        return $this->render(
            'application/partials/nav/_version.html.twig',
            [
                'changes' => $changes,
                'version' => $version,
                'popup' => $popup,
            ]
        );
    }
}
