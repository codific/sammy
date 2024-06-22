<?php

declare(strict_types=1);

namespace App\ViewParametersProvider\Assessment;

use App\Entity\AssessmentStream;
use App\Entity\User;
use App\Enum\ImprovementStatus;
use App\Form\Application\AuditType;
use App\Form\Application\EditValidationType;
use App\Form\Application\ImprovementType;
use App\Form\Application\RetractSubmissionType;
use App\Form\Application\ValidationType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;

readonly class AssessmentFormProvider
{
    public function __construct(
        private FormFactoryInterface $formFactory
    ) {
    }

    public function getEditValidationForm(): FormView
    {
        return $this->formFactory->create(EditValidationType::class)->createView();
    }

    public function getRetractForm(): FormView
    {
        return $this->formFactory->create(RetractSubmissionType::class)->createView();
    }

    public function getValidationForm(AssessmentStream $assessmentStream): FormView
    {
        return $this->formFactory->create(
            ValidationType::class,
            ['remarks' => $assessmentStream->getLastValidationStage()?->getComment() ?? '']
        )->createView();
    }

    public function getImprovementForm(AssessmentStream $assessmentStream, ?User $user = null): FormView
    {
        return $this->formFactory->create(ImprovementType::class, null, [
            ImprovementType::DATE_FORMAT => $user?->getDateFormat() ?? 'yyyy-MM-dd',
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
        ])->createView();
    }

    public function getAuditForm(AssessmentStream $assessmentStream): FormView
    {
        return $this->formFactory->create(
            AuditType::class,
            ['remarks' => $assessmentStream->getLastValidationStage()?->getComment() ?? $assessmentStream->getLastEvaluationStage()?->getComment() ?? '']
        )->createView();
    }
}
