<?php

declare(strict_types=1);

namespace App\ViewParametersProvider\Assessment;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Practice;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentAnswerType;
use App\Enum\AssessmentStatus;
use App\Enum\StageType;
use App\Service\AssessmentAnswersService;
use App\Service\AssessmentService;
use App\Service\AssessmentStreamService;
use App\Service\AssignmentService;
use App\Service\MetamodelService;
use App\Service\QuestionnaireService;
use App\Service\ScoreService;
use App\Service\TemplateService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;

class AssessmentInfoProvider
{
    private array $savedAnswers = [];

    public function __construct(
        private readonly QuestionnaireService $questionnaireService,
        private readonly AssessmentAnswersService $assessmentAnswersService,
        private readonly AssessmentService $assessmentService,
        private readonly AssignmentService $assignmentService,
        private readonly AssessmentStreamService $assessmentStreamService,
        private readonly TemplateService $templateService,
        private readonly AssessmentFormProvider $assessmentFormProvider,
        private readonly MetamodelService $metamodelService,
        private readonly UserService $userService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ScoreService $scoreService
    ) {
    }

    public function getAuditParams(User $user, Assessment $assessment, Stream $stream, $activeTab = 'audit'): array
    {
        $assessmentStream = $this->assessmentStreamService->getAssessmentStream($assessment, $stream);
        $this->savedAnswers = $this->assessmentAnswersService->getAssessmentAnswers($assessment);

        $lastStage = $assessmentStream->getCurrentStage();
        $auditorReadonlyException = $lastStage !== null && in_array($assessmentStream->getCurrentStage()::class, [Evaluation::class, Validation::class], true);

        $showValidation = $assessmentStream->getCurrentStage() instanceof Improvement;
        $partialParams = [];
        if ($showValidation) {
            $partialParams['evaluationParams'] = $this->getEvaluationTabParams($assessmentStream, $user);
            $partialParams['validationParams'] = $this->getValidationParams($assessmentStream, $user);
            $partialParams['assignment'] = $partialParams['validationParams']['assignment'];
        } else {
            $partialParams = $this->getEvaluationTabParams($assessmentStream, $user, $auditorReadonlyException);
            $partialParams['auditForm'] = $this->assessmentFormProvider->getAuditForm($assessmentStream);
        }
        $partialParams['showValidation'] = $showValidation;

        return [
            'auditView' => true,
            'assessmentStream' => $assessmentStream,
            'streams' => $this->getStreams($assessmentStream->getStream()->getPractice(), $this->savedAnswers, $assessment),
            'businessFunctions' => $this->metamodelService->getBusinessFunctions($assessment->getProject()->getMetamodel()),
            'progress' => $this->assessmentService->getProgress($assessment, savedAnswers: count($this->savedAnswers)),
            'partialParams' => $partialParams,
            'activeTab' => $activeTab,
            'currentTab' => $this->getCurrentTab($assessmentStream),
        ];
    }

    public function getModelParams(User $user, Assessment $assessment, Stream $stream, StageType $stageType): array
    {
        $assessmentStream = $this->assessmentStreamService->getAssessmentStream($assessment, $stream);
        $this->savedAnswers = $this->assessmentAnswersService->getAssessmentAnswers($assessment);

        if ($stageType === StageType::EVALUATION) {
            $activeTab = 'evaluation';
            $partialParams = $this->getEvaluationTabParams($assessmentStream, $user);
        } elseif ($stageType === StageType::VALIDATION) {
            $partialParams = $this->getValidationParams($assessmentStream, $user);
            $activeTab = 'validation';
        } else {
            $partialParams = $this->getImprovementParams($assessmentStream, $user);
            $activeTab = 'improvement';
        }

        return [
            'assessmentStream' => $assessmentStream,
            'streams' => $this->getStreams($assessmentStream->getStream()->getPractice(), $this->savedAnswers, $assessment),
            'businessFunctions' => $this->metamodelService->getBusinessFunctions($assessment->getProject()->getMetamodel()),
            'progress' => $this->assessmentService->getProgress($assessment, savedAnswers: count($this->savedAnswers)),
            'partialParams' => $partialParams,
            'activeTab' => $activeTab,
            'currentTab' => $this->getCurrentTab($assessmentStream),
        ];
    }

    private function getCurrentTab(?AssessmentStream $assessmentStream): string
    {
        return match ($assessmentStream?->getStatus()) {
            AssessmentStatus::IN_VALIDATION => 'validation',
            AssessmentStatus::IN_IMPROVEMENT, AssessmentStatus::VALIDATED, AssessmentStatus::COMPLETE => 'improvement',
            default => 'evaluation'
        };
    }

    /**
     * @return Question[]
     */
    private function getQuestions(Stream $stream): array
    {
        return $this->metamodelService->getQuestionsByStream($stream);
    }

    private function getStreams(Practice $practice, array $savedAnswers, Assessment $assessment): array
    {
        return $practice->getPracticeStreams()->map(
            fn ($practiceStream) => [
                'stream' => $practiceStream,
                'completed' => $this->questionnaireService->isStreamCompleted($practiceStream, $savedAnswers),
                'status' => $assessment->getAssessmentAssessmentStreams()->filter(
                    fn (AssessmentStream $assessmentStream) => (
                        $assessmentStream->getStream() === $practiceStream
                        && $assessmentStream->getStatus() !== \App\Enum\AssessmentStatus::ARCHIVED
                    )
                )->last()->getStatus(),
            ]
        )->toArray();
    }

    private function getValidationParams(AssessmentStream $assessmentStream, User $user): array
    {
        return match ($assessmentStream->getStatus()) {
            \App\Enum\AssessmentStatus::IN_VALIDATION,
            \App\Enum\AssessmentStatus::VALIDATED,
            \App\Enum\AssessmentStatus::IN_IMPROVEMENT,
            \App\Enum\AssessmentStatus::COMPLETE => $this->getValidationTabParams($assessmentStream, $user),
            default => []
        };
    }

    private function getImprovementParams(AssessmentStream $assessmentStream, User $user): array
    {
        return match ($assessmentStream->getStatus()) {
            \App\Enum\AssessmentStatus::VALIDATED,
            \App\Enum\AssessmentStatus::IN_IMPROVEMENT,
            \App\Enum\AssessmentStatus::COMPLETE => $this->getImprovementTabParams($assessmentStream, $user),
            default => []
        };
    }

    public function getEvaluationTabParams(AssessmentStream $assessmentStream, User $currentUser, bool $auditorReadonlyException = false): array
    {
        return array_merge(
            ['evaluation' => $assessmentStream->getLastEvaluationStage()],
            ['readOnly' => $this->getReadOnly(Stage::EVALUATION, $assessmentStream, $currentUser) && !$auditorReadonlyException],
            ['showRestrictedNote' => $this->getShowRestrictedNote(Stage::EVALUATION, $assessmentStream, $currentUser)],
            ['showAssignmentPopup' => $this->getShowAssignmentPopup(Stage::EVALUATION, $assessmentStream, $currentUser)],
            ['assignment' => $this->getAssignment(Stage::EVALUATION, $assessmentStream)],
            ['questions' => $this->getQuestions($assessmentStream->getStream())],
            ['savedAnswers' => $this->savedAnswers],
            ['templateAnswers' => $this->getTemplateAnswers($assessmentStream)],
            ['templateRemarks' => $this->getTemplateRemarks($assessmentStream)],
            ['fullyAnswered' => $this->questionnaireService->isStreamCompleted($assessmentStream->getStream(), $this->savedAnswers)],
            ['oldAnswers' => $this->assessmentAnswersService->getAssessmentStreamPreviousAnswers($assessmentStream)],
            ['canRetractStream' => $this->assessmentStreamService->canStreamBeRetracted($currentUser, $assessmentStream)],
            ['retractForm' => $this->assessmentFormProvider->getRetractForm()],
            ['auditAnswers' => $this->scoreService->getExternallyVerifiedScoreByQuestion($assessmentStream->getAssessment())],
        );
    }

    private function getValidationTabParams(AssessmentStream $assessmentStream, User $currentUser): array
    {
        return array_merge(
            ['validation' => $assessmentStream->getLastValidationStage()],
            ['readOnly' => $this->getReadOnly(Stage::VALIDATION, $assessmentStream, $currentUser)],
            ['showRestrictedNote' => $this->getShowRestrictedNote(Stage::VALIDATION, $assessmentStream, $currentUser)],
            ['showAssignmentPopup' => $this->getShowAssignmentPopup(Stage::VALIDATION, $assessmentStream, $currentUser)],
            ['assignment' => $this->getAssignment(Stage::VALIDATION, $assessmentStream)],
            ['validationForm' => $this->assessmentFormProvider->getValidationForm($assessmentStream)],
            ['canEditValidation' => $this->assessmentService->canEditValidation($currentUser, $assessmentStream)],
            ['editValidationForm' => $this->assessmentFormProvider->getEditValidationForm()],
            ['assessmentStreamDesiredAnswers' => ($assessmentStream->getLastImprovementStage() !== null) ?
                $this->assessmentAnswersService->getLatestAssessmentStreamAnswers($assessmentStream, AssessmentAnswerType::DESIRED) : [],
            ]
        );
    }

    private function getImprovementTabParams(AssessmentStream $assessmentStream, User $currentUser): array
    {
        $improvementStage = $assessmentStream->getLastImprovementStage();

        return array_merge(
            ['improvement' => $improvementStage],
            ['readOnly' => $this->getReadOnly(Stage::IMPROVEMENT, $assessmentStream, $currentUser)],
            ['showRestrictedNote' => $this->getShowRestrictedNote(Stage::IMPROVEMENT, $assessmentStream, $currentUser)],
            ['showAssignmentPopup' => $this->getShowAssignmentPopup(Stage::IMPROVEMENT, $assessmentStream, $currentUser)],
            ['assignment' => $this->getAssignment(Stage::IMPROVEMENT, $assessmentStream)],
            ['questions' => $this->getQuestions($assessmentStream->getStream())],
            ['savedAnswers' => $this->savedAnswers],
            [
                'plannedAnswers' => $this->assessmentAnswersService->getStructuredAssessmentStreamAnswers(
                    $assessmentStream,
                    \App\Enum\AssessmentAnswerType::DESIRED
                ),
            ],
            ['userHasImprovementRights' => $this->userService->userHasImproverRights($improvementStage, $currentUser)],
            ['improvementForm' => $this->assessmentFormProvider->getImprovementForm($assessmentStream, $currentUser)],
            ['templateAnswers' => $this->getTemplateAnswers($assessmentStream)],
            ['templateRemarks' => $this->getTemplateRemarks($assessmentStream)],
        );
    }

    private function getShowRestrictedNote($stageName, AssessmentStream $assessmentStream, User $currentUser): bool
    {
        $stageIsActive = fn () => $stageName === $assessmentStream->getActiveStageName();
        $userHasNoAccess = fn () => !$this->assessmentService->getUserAccess($assessmentStream, $currentUser);

        return $stageIsActive() && $userHasNoAccess();
    }

    private function getReadOnly($stageName, AssessmentStream $assessmentStream, User $user): bool
    {
        $stageIsNotActive = fn () => $stageName !== $assessmentStream->getActiveStageName();
        $userHasNoAccess = fn () => !$this->assessmentService->getUserAccess($assessmentStream, $user);

        return $stageIsNotActive() || $userHasNoAccess();
    }

    private function getShowAssignmentPopup($stageName, AssessmentStream $assessmentStream, User $currentUser): bool
    {
        $assignment = $this->getAssignment($stageName, $assessmentStream);

        $this->entityManager->getFilters()->disable('deleted_entity');
        /** @var ?User $user */
        $user = $assignment?->getUser();
        $showPopup = ($user !== null && $user->getDeletedAt() === null && $user->getId() !== $currentUser->getId());
        $this->entityManager->getFilters()->enable('deleted_entity');

        return $showPopup;
    }

    private function getAssignment($stageName, AssessmentStream $assessmentStream): ?Assignment
    {
        $stage = $assessmentStream->getStage($stageName);

        return $this->assignmentService->getStageAssignment($stage);
    }

    private function getTemplateAnswers(AssessmentStream $assessmentStream): array
    {
        $templateProject = $assessmentStream->getAssessment()->getProject()->getTemplateProject();

        return $templateProject !== null ? $this->assessmentAnswersService->getAssessmentAnswers($templateProject->getAssessment()) : [];
    }

    private function getTemplateRemarks(AssessmentStream $assessmentStream): array
    {
        $templateProject = $assessmentStream->getAssessment()->getProject()->getTemplateProject();

        return $templateProject !== null ? $this->templateService->getTemplateRemarksByAssessmentStream($assessmentStream) : [];
    }
}
