<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\AssessmentStream;
use App\Entity\Stream;
use App\Enum\AssessmentStatus;
use App\Exception\SaveRemarkOnIncorrectStreamException;
use App\Form\Application\AuditType;
use App\Service\AssessmentService;
use App\Service\AssessmentStreamService;
use App\Service\ProjectService;
use App\ViewParametersProvider\Assessment\AssessmentInfoProvider;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/audit', name: 'audit_')]
class AuditController extends AbstractController
{
    #[Route('/{id}/', name: 'overview', requirements: ['id' => "\d+"], methods: ['GET'])]
    public function evaluation(
        ProjectService $projectService,
        AssessmentInfoProvider $infoProvider,
        AssessmentStreamService $assessmentStreamService,
        Stream $stream,
        Request $request,
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
        if ($auditView === false) {
            return $this->redirectToRoute('app_model_showStream', ['id' => $stream->getId()]);
        }

        return $this->render(
            'application/model/model.html.twig',
            $infoProvider->getAuditParams($user, $assessment, $stream)
        );
    }

    /**
     * @throws SaveRemarkOnIncorrectStreamException
     * @throws NonUniqueResultException
     */
    #[Route('/submit/{id}', name: 'submit', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted(new Expression("is_granted('SUBMIT_STREAM', subject) or is_granted('VALIDATE_STREAM', subject)"), 'assessmentStream')]
    public function validate(Request $request, AssessmentStream $assessmentStream, AssessmentService $assessmentService): Response
    {
        $currentUser = $this->getUser();

        $form = $this->createForm(AuditType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', $this->translator->trans('application.assessment.audit_stream_error', [], 'application'));

            return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }

        $data = $form->getData();
        $remarks = $data['remarks'];
        $buttonName = $form->getClickedButton()->getName();

        if ($buttonName === AuditType::SAVE_BUTTON) {
            try {
                $assessmentService->saveEvaluationRemark($assessmentStream, $remarks, $currentUser);
                if ($assessmentStream->getLastValidationStage() !== null) {
                    $assessmentService->saveValidationRemark($assessmentStream, $remarks, $currentUser);
                }
            } catch (SaveRemarkOnIncorrectStreamException) {
                $this->addFlash('error', 'Something went wrong with saving your remarks data');

                return $this->safeRedirect($request, 'app_index');
            }
            $this->addFlash('success', $this->translator->trans('application.assessment.save_validation_remark_success', [], 'application'));

            return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        } else {
            if (in_array($assessmentStream->getStatus(), [AssessmentStatus::NEW, AssessmentStatus::IN_EVALUATION], true)) {
                $assessmentService->submitStreamWithoutAutoValidateAttempt($assessmentStream, $currentUser);
            }
            $assessmentService->validateStream($assessmentStream, $currentUser, $remarks);
            $this->addFlash('success', $this->translator->trans('application.assessment.validate_stream_accepted', [], 'application'));

            return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }
    }

    #[Route('/autosave/{id}', name: 'autosave', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted(new Expression("is_granted('SUBMIT_STREAM', subject) or is_granted('VALIDATE_STREAM', subject)"), 'assessmentStream')]
    public function autosave(Request $request, AssessmentStream $assessmentStream, AssessmentService $assessmentService): JsonResponse
    {
        $remarks = $request->request->get('remarks');

        if ($assessmentStream->getLastEvaluationStage()?->getComment() === $remarks) {
            return new JsonResponse('No changes', Response::HTTP_OK);
        } else {
            try {
                $assessmentService->saveEvaluationRemark($assessmentStream, $remarks, $this->getUser());
                if ($assessmentStream->getLastValidationStage() !== null) {
                    $assessmentService->saveValidationRemark($assessmentStream, $remarks, $this->getUser());
                }
            } catch (SaveRemarkOnIncorrectStreamException) {
                return new JsonResponse('Something went wrong with saving your remarks data', Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse('Remarks changes successfully saved', Response::HTTP_OK);
        }
    }
}
