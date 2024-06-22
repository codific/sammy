<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\AssessmentStream;
use App\Entity\Remark;
use App\Entity\Stream;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\StageType;
use App\Enum\ValidationStatus;
use App\Exception\SaveRemarkOnIncorrectStreamException;
use App\Exception\WrongStageException;
use App\Form\Application\EditValidationType;
use App\Form\Application\ValidationType;
use App\Service\AssessmentService;
use App\Service\AssessmentStreamService;
use App\Service\ProjectService;
use App\Service\RemarkService;
use App\Service\SanitizerService;
use App\ViewParametersProvider\Assessment\AssessmentInfoProvider;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/validation', name: 'validation_')]
class ValidationController extends AbstractController
{
    #[Route('/{id}', name: 'overview', requirements: ['id' => "\d+"], methods: ['GET'])]
    public function validation(
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

        if (in_array($assessmentStream->getStatus(), [AssessmentStatus::NEW, AssessmentStatus::IN_EVALUATION], true)) {
            return $this->redirectToRoute('app_evaluation_overview', ['id' => $stream->getId()]);
        }

        $auditView = $this->getUser()->isAuditor() && $request->cookies->get('audit-view-toggle') !== 'false';
        if ($auditView) {
            return $this->render(
                'application/model/model.html.twig',
                $infoProvider->getAuditParams($user, $assessment, $stream)
            );
        }

        return $this->render(
            'application/model/model.html.twig',
            $infoProvider->getModelParams($user, $assessment, $stream, StageType::VALIDATION)
        );
    }

    #[Route('/delete-remark/{id}', name: 'delete_remark', requirements: ['id' => "\d+"], methods: ['DELETE'])]
    #[IsGranted('DELETE_REMARK', 'remark')]
    #[IsGranted(new Expression('is_granted("ASSESSMENT_STREAM_ACCESS", subject.getStage().getAssessmentStream())'), 'remark')]
    public function deleteRemark(Remark $remark, RemarkService $remarkService): Response
    {
        $remarkService->deleteRemark($remark);

        return new JsonResponse($this->translator->trans('application.assessment.delete_remark_success', [], 'application'), Response::HTTP_OK);
    }

    /**
     * @throws SaveRemarkOnIncorrectStreamException
     * @throws NonUniqueResultException
     */
    #[Route('/validate/{id}', name: 'validate', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('VALIDATE_STREAM', 'assessmentStream')]
    public function validate(Request $request, AssessmentStream $assessmentStream, AssessmentService $assessmentService): Response
    {
        $currentUser = $this->getUser();

        $form = $this->createForm(ValidationType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('danger', $this->translator->trans('application.assessment.validate_stream_error', [], 'application'));

            return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }

        $data = $form->getData();
        $remarks = $data['remarks'];
        $buttonName = $form->getClickedButton()->getName();

        if ($buttonName === ValidationType::SAVE_BUTTON) {
            $assessmentService->saveValidationRemark($assessmentStream, $remarks, $currentUser);
            $this->addFlash('success', $this->translator->trans('application.assessment.save_validation_remark_success', [], 'application'));

            return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        } else {
            $status = ValidationStatus::fromLabel($buttonName);
            $assessmentService->validateStream($assessmentStream, $currentUser, $remarks, $status);
            $this->addFlash('success', $this->translator->trans('application.assessment.validate_stream_'.strtolower($status->label())));

            return $this->redirectToRoute('app_improvement_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }
    }

    #[Route('/autosave/{id}', name: 'autosave', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('VALIDATE_STREAM', 'assessmentStream')]
    public function autosave(Request $request, AssessmentStream $assessmentStream, AssessmentService $assessmentService): JsonResponse
    {
        $currentUser = $this->getUser();
        $remarks = $request->request->get('remarks');

        if ($assessmentStream->getLastValidationStage()->getComment() === $remarks) {
            return new JsonResponse('', Response::HTTP_FOUND);
        }

        try {
            $assessmentService->saveValidationRemark($assessmentStream, $remarks, $currentUser);
        } catch (SaveRemarkOnIncorrectStreamException $exception) {
            return new JsonResponse('Something went wrong with saving your remarks data', Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse('Remarks changes successfully saved', Response::HTTP_OK);
    }

    #[Route('/edit-validation/{id}', name: 'edit_validation', requirements: ['id' => "\d+"], methods: ['POST'])]
    #[IsGranted('EDIT_VALIDATION', 'assessmentStream')]
    public function editValidation(Request $request, AssessmentStream $assessmentStream, AssessmentService $assessmentService, SanitizerService $sanitizerService): JsonResponse
    {
        $form = $this->createForm(EditValidationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $remarks = $data['remarks'];
            $assessmentService->editValidation($assessmentStream, $remarks);

            return new JsonResponse(
                [
                    'msg' => $this->translator->trans('application.assessment.edit_validation_success', [], 'application'),
                    'value' => $sanitizerService->sanitizeEntityValue(
                        $assessmentStream->getLastValidationStage()->getComment() ?? '',
                        'comment',
                        $assessmentStream
                    ),
                ],
                Response::HTTP_OK
            );
        }

        return new JsonResponse('', Response::HTTP_BAD_REQUEST);
    }

    #[Route('/delete-validation-remark/{id}', name: 'delete_validation_remark', requirements: ['id' => "\d+"], methods: ['DELETE'])]
    #[IsGranted(new Expression('is_granted("EDIT_VALIDATION", subject.getAssessmentStream())'), 'validation')]
    public function deleteValidationRemark(Validation $validation, RemarkService $remarkService): JsonResponse
    {
        $remarkService->deleteValidationRemark($validation);

        return new JsonResponse($this->translator->trans('application.assessment.delete_remark_success', [], 'application'), Response::HTTP_OK);
    }

    #[Route('/undoValidation/{assessmentStream}', name: 'undo_validation', requirements: ['assessmentStream' => "\d+"], methods: ['POST'])]
    public function undoValidation(
        AssessmentStream $assessmentStream,
        AssessmentService $assessmentService
    ): RedirectResponse {
        if (!$this->isGranted('UNDO_VALIDATION', $assessmentStream)) {
            $this->addFlash('danger', $this->translator->trans('application.stream.retract_error', [], 'application'));

            return $this->redirectToRoute('app_evaluation_overview', ['id' => $assessmentStream->getStream()->getId()]);
        }

        try {
            $assessmentService->undoValidation($assessmentStream);
            $this->addFlash('success', $this->translator->trans('application.assessment.validate_stream_'.strtolower(ValidationStatus::RETRACTED->label())));
        } catch (WrongStageException|OptimisticLockException|InvalidArgumentException $exception) {
            $this->addFlash('danger', $this->translator->trans('application.stream.retract_error', [], 'application'));
        }

        return $this->redirectToRoute('app_validation_overview', ['id' => $assessmentStream->getStream()->getId()]);
    }
}
