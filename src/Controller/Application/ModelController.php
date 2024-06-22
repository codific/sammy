<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\AssessmentStream;
use App\Entity\Practice;
use App\Entity\Stream;
use App\Enum\AssessmentStatus;
use App\Repository\AssignmentRepository;
use App\Service\AssessmentStreamService;
use App\Service\MetamodelService;
use App\Service\ProjectService;
use App\Service\StagesTimelineService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/model', name: 'model_')]
class ModelController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     */
    #[Route('/showPractice/{id}', name: 'showPractice', requirements: ['id' => "\d+"], defaults: ['id' => 0], methods: ['GET'])]
    public function showPractice(ProjectService $projectService, MetamodelService $metamodelService, AssessmentStreamService $assessmentStreamService, ?Practice $practice): Response
    {
        $assessment = $projectService->getCurrentProject()?->getAssessment();
        if ($assessment === null) {
            return $this->redirectToRoute('app_index');
        }
        if ($practice === null) {
            $businessFunctions = $metamodelService->getBusinessFunctions($assessment->getProject()->getMetamodel());
            $practice = $businessFunctions[0]->getBusinessFunctionPractices()[0];
        }
        $stream = $practice->getPracticeStreams()[0];

        $assessmentStream = $assessmentStreamService->getAssessmentStream($assessment, $stream);

        return $this->showStage($assessmentStream, $stream);
    }

    #[Route('/showStream/{id}', name: 'showStream', requirements: ['id' => "\d+"], defaults: ['id' => 0], methods: ['GET'])]
    public function showStream(ProjectService $projectService, AssessmentStreamService $assessmentStreamService, ?Stream $stream): Response
    {
        $assessment = $projectService->getCurrentProject()?->getAssessment();
        if ($assessment === null) {
            return $this->redirectToRoute('app_index');
        }
        if ($stream === null) {
            return $this->redirectToRoute('app_model_showPractice');
        }

        $assessmentStream = $assessmentStreamService->getAssessmentStream($assessment, $stream);

        return $this->showStage($assessmentStream, $stream);
    }

    private function showStage(?AssessmentStream $assessmentStream, Stream $stream): RedirectResponse
    {
        return match ($assessmentStream?->getStatus()) {
            AssessmentStatus::IN_VALIDATION => $this->redirectToRoute('app_validation_overview', ['id' => $stream->getId()]),
            AssessmentStatus::IN_IMPROVEMENT, AssessmentStatus::VALIDATED, AssessmentStatus::COMPLETE => $this->redirectToRoute('app_improvement_overview', ['id' => $stream->getId()]),
            default => $this->redirectToRoute('app_evaluation_overview', ['id' => $stream->getId()])
        };
    }

    /**
     * This is NOT unused, do NOT delete.
     */
    public function assessmentStreamTimeline(AssessmentStream $assessmentStream, StagesTimelineService $timelineService): Response
    {
        return $this->render(
            'application/model/partials/_timeline.html.twig',
            [
                'timelineEvents' => $timelineService->getTimelineEvents($assessmentStream),
            ]
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/activeAssignments', name: 'active_assignments', methods: ['GET'])]
    public function activeAssignments(AssignmentRepository $assignmentRepository): Response
    {
        $currentUser = $this->getUser();
        $project = $this->requestStack->getSession()->get(ProjectService::CURRENT_PROJECT_SESSION_KEY);
        $activeUserAssignments = 0;
        if ($project !== null) {
            $activeUserAssignments = sizeof($assignmentRepository->findActiveForProjectAndUser($project, $currentUser));
        }

        return $this->render('application/partials/nav/_active_assignments_header_navbar.html.twig', ['number' => $activeUserAssignments]);
    }
}
