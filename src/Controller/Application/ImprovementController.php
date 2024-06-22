<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\AssessmentStream;
use App\Entity\Improvement;
use App\Entity\Stream;
use App\Enum\AssessmentStatus;
use App\Enum\ImprovementStatus;
use App\Enum\StageType;
use App\Exception\QueueNotOnlineException;
use App\Exception\SavePlanOnIncorrectStreamException;
use App\Form\Application\ImprovementType;
use App\Service\AssessmentService;
use App\Service\AssessmentStreamService;
use App\Service\ProjectService;
use App\Service\SanitizerService;
use App\ViewParametersProvider\Assessment\AssessmentInfoProvider;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/improvement', name: 'improvement_')]
class ImprovementController extends AbstractController
{
    #[Route('/{id}', name: 'overview', requirements: ['id' => "\d+"], methods: ['GET'])]
    public function improvement(
        ProjectService $projectService,
        AssessmentInfoProvider $infoProvider,
        AssessmentStreamService $assessmentStreamService,
        Stream $stream,
        Request $request
    ): Response {
        $user = $this->getUser();

        $assessment = $projectService->getCurrentProject()?->getAssessment();
        if ($assessment === null) {
            return $this->redirectToRoute('app_index');
        }

        $assessmentStream = $assessmentStreamService->getAssessmentStream($assessment, $stream);
        if (!$assessmentStream instanceof AssessmentStream) {
            return $this->redirectToRoute('app_index');
        }

        if (in_array($assessmentStream->getStatus(), [AssessmentStatus::NEW, AssessmentStatus::IN_EVALUATION, AssessmentStatus::IN_VALIDATION], true)) {
            return $this->redirectToRoute('app_validation_overview', ['id' => $stream->getId()]);
        }

        $auditView = $this->getUser()->isAuditor() && $request->cookies->get('audit-view-toggle') !== 'false';

        return $this->render(
            'application/model/model.html.twig',
            array_merge($infoProvider->getModelParams($user, $assessment, $stream, StageType::IMPROVEMENT), ['auditView' => $auditView])
        );
    }

    /**
     * @throws NonUniqueResultException
     * @throws SavePlanOnIncorrectStreamException|\App\Exception\QueueNotOnlineException
     */
    #[Route('/start-improve/{id}', name: 'start_improve', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('START_IMPROVE_STREAM', 'assessmentStream')]
    public function startImprove(
        Request $request,
        AssessmentStream $assessmentStream,
        AssessmentService $assessmentService,
        SanitizerService $sanitizer
    ): RedirectResponse {
        $currentUser = $this->getUser();

        $form = $this->createForm(ImprovementType::class, null, [
            ImprovementType::DATE_FORMAT => $currentUser->getDateFormat(),
            ImprovementType::CHOSEN_DATE => $assessmentStream->getLastImprovementStage()->getTargetDate(),
            ImprovementType::WRITTEN_PLAN => $assessmentStream->getLastImprovementStage()->getPlan(),
            ImprovementType::SHOW_SAVE_TEMP_PLAN_BUTTON => in_array(
                $assessmentStream->getLastImprovementStage()?->getStatus(),
                [
                    ImprovementStatus::NEW,
                    ImprovementStatus::DRAFT,
                ],
                true
            ),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', $this->translator->trans('application.assessment.improvement_bad_data', [], 'application'));

            return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }

        $data = $form->getData();
        $targetDate = $data['targetDate'];
        $plan = $sanitizer->sanitizeWordValue($data['plan'], 'img');
        $newDesiredAnswers = json_decode($data['newDesiredAnswers'], true);

        $buttonName = $form->getClickedButton()?->getName();
        if ($buttonName === ImprovementType::SAVE_BUTTON) {
            if ($targetDate !== '' || $plan !== '') {
                $improvement = $assessmentStream->getLastImprovementStage();
                $assessmentService->saveImprovementPlan($improvement, $targetDate, $plan, $newDesiredAnswers, $currentUser);
                $this->addFlash('success', $this->translator->trans('application.assessment.save_improvement_plan_success', [], 'application'));

                return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
            }
        } elseif ($buttonName === ImprovementType::CANCEL_BUTTON) {
            $assessmentService->cancelImprovementStream($assessmentStream->getLastImprovementStage(), $currentUser);
            $this->addFlash('success', $this->translator->trans('application.assessment.cancel_improve_stream_success', [], 'application'));

            return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
        } elseif ($buttonName === ImprovementType::SUBMIT_BUTTON) {
            if ($targetDate !== '' && $plan !== '') {
                $assessmentService->startImprovementStream($assessmentStream->getLastImprovementStage(), $targetDate, $plan, $newDesiredAnswers, $currentUser);
                $this->addFlash('success', $this->translator->trans('application.assessment.start_improve_stream_success', [], 'application'));

                return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
            }
        }
        $this->addFlash('danger', $this->translator->trans('application.assessment.improvement_bad_data', [], 'application'));

        return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
    }

    /**
     * @throws QueueNotOnlineException
     */
    #[Route('/complete-improve/{id}', name: 'complete_improve', requirements: ['id' => "\d+"], methods: ['GET', 'POST'])]
    #[IsGranted('START_IMPROVE_STREAM', 'assessmentStream')]
    public function completeImprove(Request $request, AssessmentStream $assessmentStream, AssessmentService $assessmentService): RedirectResponse
    {
        $assessmentService->completeImprovementStream($assessmentStream->getLastImprovementStage());

        return $this->safeRedirect($request, 'app_dashboard_index');
    }

    #[Route('/reactivate-improvement/{id}', name: 'reactivate_improvement', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('FINISH_IMPROVEMENT_STREAM', 'improvement')]
    public function reactivateImprovement(Improvement $improvement, AssessmentService $assessmentService): JsonResponse
    {
        $assessmentService->reactivateImprovementStream($improvement);
        $this->addFlash('success', $this->translator->trans('application.assessment.reactivate_improve_stream_success', [], 'application'));

        return new JsonResponse('', Response::HTTP_OK);
    }

    /**
     * @throws QueueNotOnlineException
     */
    #[Route('/finish-improvement/{id}', name: 'finish_improvement', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('FINISH_IMPROVEMENT_STREAM', 'improvement')]
    #[IsGranted(new Expression('is_granted("ASSESSMENT_STREAM_ACCESS", subject.getAssessmentStream())'), 'improvement')]
    public function finishImprovement(Improvement $improvement, AssessmentService $assessmentService): JsonResponse
    {
        $assessmentService->finishImprovementStream($improvement, $this->getUser());
        $this->addFlash('success', $this->translator->trans('application.assessment.finish_improve_stream_success', [], 'application'));

        return new JsonResponse('', Response::HTTP_OK);
    }
}
