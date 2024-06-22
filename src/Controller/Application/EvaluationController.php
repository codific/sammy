<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\AssessmentStream;
use App\Entity\Stream;
use App\Enum\AssessmentStatus;
use App\Enum\StageType;
use App\Enum\ValidationStatus;
use App\Exception\WrongStageException;
use App\Form\Application\RetractSubmissionType;
use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use App\Service\AssessmentAnswersService;
use App\Service\AssessmentService;
use App\Service\AssessmentStreamService;
use App\Service\ProjectService;
use App\ViewParametersProvider\Assessment\AssessmentInfoProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/evaluation', name: 'evaluation_')]
class EvaluationController extends AbstractController
{
    #[Route('/{id}/', name: 'overview', requirements: ['id' => "\d+"], methods: ['GET'])]
    public function evaluation(
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

        $auditView = $this->getUser()->isAuditor() && $request->cookies->get('audit-view-toggle') !== 'false';
        if ($auditView) {
            return $this->redirectToRoute('app_audit_overview', ['id' => $stream->getId()]);
        }

        return $this->render(
            'application/model/model.html.twig',
            $infoProvider->getModelParams($user, $assessmentStream->getAssessment(), $stream, StageType::EVALUATION)
        );
    }

    #[Route('/submit/{assessmentStream}', name: 'submit', requirements: ['assessmentStream' => "\d+"], methods: ['POST'])]
    #[IsGranted('SUBMIT_STREAM', 'assessmentStream')]
    public function submit(
        Request $request,
        AssessmentStream $assessmentStream,
        AssessmentService $assessmentService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('submit_stream', $request->request->get('_csrf_token'))) {
            return $this->safeRedirect($request, 'app_index');
        }

        $currentUser = $this->getUser();
        $assessmentService->submitStreamWithAutoValidateAttempt($assessmentStream, $currentUser);
        $this->addFlash('success', $this->translator->trans('application.assessment.submit_stream_success', [], 'application'));
        if ($assessmentStream->getStatus() === AssessmentStatus::VALIDATED) {
            $this->addFlash('success', $this->translator->trans('application.assessment.validate_stream_auto_accepted', [], 'application'));
        }

        // if the evaluation is autoaccepted we should redirect to improvement
        if ($assessmentStream->getStatus() === AssessmentStatus::VALIDATED) {
            return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
        } else {
            return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws WrongStageException
     */
    #[Route('/retractSubmission/{assessmentStream}', name: 'retract_submission', requirements: ['assessmentStream' => "\d+"], methods: ['POST'])]
    public function retractSubmission(
        Request $request,
        AssessmentStream $assessmentStream,
        AssessmentService $assessmentService
    ): RedirectResponse {
        if (!$this->isGranted('RETRACT_STREAM', $assessmentStream)) {
            $this->addFlash('danger', $this->translator->trans('application.stream.retract_error', [], 'application'));

            return $this->redirectToRoute('app_evaluation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }

        $form = $this->createForm(RetractSubmissionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $this->getUser();
            $assessmentService->retractStreamSubmission($assessmentStream, $currentUser);
            $this->addFlash('success', $this->translator->trans('application.assessment.validate_stream_'.strtolower(ValidationStatus::RETRACTED->label())));
        }

        return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/save-choice/{id}', name: 'save_choice', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('ANSWER_STREAM', 'assessmentStream')]
    public function saveChoice(
        Request $request,
        AssessmentStream $assessmentStream,
        AssessmentAnswersService $assessmentAnswersService,
        AnswerRepository $answerRepository,
        QuestionRepository $questionRepository,
        AssessmentService $assessmentService,
        AssessmentStreamService $assessmentStreamService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $currentUser = $this->getUser();

        $answerId = $request->request->getInt('answerId');
        $questionId = $request->request->getInt('questionId');

        $answer = $answerRepository->findOneBy(['id' => $answerId]);
        $question = $questionRepository->findOneBy(['id' => $questionId]);
        if ($question === null || $answer === null || !in_array($answer, $question->getAnswers(), true)) {
            return new JsonResponse('', Response::HTTP_BAD_REQUEST);
        }
        $assessmentAnswersService->saveAnswer($assessmentStream, $question, $answer, $currentUser);

        if ($assessmentStream->getAssessment()->getProject()->isTemplate()) {
            $assessmentStreamService->setScoreWithCondition(
                $assessmentStream,
                fn (AssessmentStream $assessmentStream) => ($assessmentStream->getStatus() === AssessmentStatus::IN_EVALUATION)
            );
            $entityManager->flush();
        }

        return new JsonResponse(['progress' => $assessmentService->getProgress(assessment: $assessmentStream->getAssessment())]);
    }

    #[Route('/save-checkbox-choice/{id}', name: 'save_checkbox_choice', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('ANSWER_STREAM', 'assessmentStream')]
    public function saveCheckboxChoice(
        Request $request,
        AssessmentStream $assessmentStream,
        AssessmentAnswersService $assessmentAnswersService,
    ): JsonResponse {
        $currentUser = $this->getUser();

        $checkboxesJsonData = $request->request->get('checkboxesData');
        try {
            return ($assessmentAnswersService->saveCheckboxAnswers($currentUser, $assessmentStream, $checkboxesJsonData)) ?
                new JsonResponse('Successfully saved', Response::HTTP_OK) :
                new JsonResponse('An error occurred while trying to save', Response::HTTP_BAD_REQUEST);
        } catch (\Exception|\JsonException $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace()[0]);
            $this->addFlash('error', $this->translator->trans('application.general.error_save_checkbox', [], 'application'));

            return new JsonResponse('There was an error', Response::HTTP_BAD_REQUEST);
        }
    }
}
