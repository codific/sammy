<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentAnswerType;
use App\Enum\AssessmentStatus;
use App\Enum\ImprovementStatus;
use App\Enum\Role;
use App\Enum\ValidationStatus;
use App\Event\Application\Post\PostCancelImprovementStreamEvent;
use App\Event\Application\Post\PostCompleteImprovementStreamEvent;
use App\Event\Application\Post\PostFinishImprovementStreamEvent;
use App\Event\Application\Post\PostReactivateImprovementStreamEvent;
use App\Event\Application\Post\PostStartImprovementStreamEvent;
use App\Event\Application\Post\PostUndoAutoValidationEvent;
use App\Event\Application\Post\PostUndoValidationStreamEvent;
use App\Event\Application\Pre\PreCancelImprovementStreamEvent;
use App\Event\Application\Pre\PreCompleteImprovementStreamEvent;
use App\Event\Application\Pre\PreFinishImprovementStreamEvent;
use App\Event\Application\Pre\PreReactivateImprovementStreamEvent;
use App\Event\Application\Pre\PreStartImprovementStreamEvent;
use App\Event\Application\Pre\PreUndoAutoValidationEvent;
use App\Event\Application\Pre\PreUndoValidationEvent;
use App\Exception\QueueNotOnlineException;
use App\Exception\SavePlanOnIncorrectStreamException;
use App\Exception\SaveRemarkOnIncorrectStreamException;
use App\Exception\SubmitStreamException;
use App\Exception\WrongStageException;
use App\Repository\AssessmentStreamRepository;
use App\Repository\QuestionRepository;
use App\Repository\StageRepository;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class AssessmentService
{
    public function __construct(
        private AssessmentAnswersService $assessmentAnswersService,
        private QuestionRepository $questionRepository,
        private EntityManagerInterface $entityManager,
        private AssessmentStreamService $assessmentStreamService,
        private AssessmentStreamRepository $assessmentStreamRepository,
        private AssignmentService $assignmentService,
        private StageService $stageService,
        private StageRepository $stageRepository,
        private AssessmentStreamFilterService $assessmentStreamFilterService,
        private MetamodelService $metamodelService,
        private QuestionnaireService $questionnaireService,
        private TagAwareCacheInterface $redisCache,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function createAssessment(Project $project): Assessment
    {
        $assessment = new Assessment();
        $this->entityManager->persist($assessment);
        $assessment->setProject($project);
        $project->setAssessment($assessment);
        $this->entityManager->persist($project);
        $this->createAssessmentStreams($assessment);
        $this->entityManager->flush();

        return $assessment;
    }

    private function createAssessmentStreams(Assessment $assessment): void
    {
        $streams = $this->metamodelService->getStreams($assessment->getProject()->getMetamodel());
        foreach ($streams as $stream) {
            $assessmentStream = new AssessmentStream();
            $assessmentStream->setAssessment($assessment);
            $assessmentStream->setStream($this->entityManager->getReference(Stream::class, $stream->getId()));
            $this->entityManager->persist($assessmentStream);
        }
    }

    public static function sortAssessmentStreams(array $assessmentStreams, array $streamWeights = []): array
    {
        $weightDeltasIndexedByStreamId = array_reduce(
            $streamWeights,
            fn($accumulator, $streamWeight) => $accumulator + [$streamWeight['streamId'] => $streamWeight['delta']],
            []
        );

        $getDelta = fn(AssessmentStream $assessmentStream) => $weightDeltasIndexedByStreamId[$assessmentStream->getStream()->getId()] ?? 0;
        $getBusinessFunctionOrder = fn(AssessmentStream $assessmentStream) => $assessmentStream->getStream()->getPractice()->getBusinessFunction()->getOrder();
        $getPracticeOrder = fn(AssessmentStream $assessmentStream) => $assessmentStream->getStream()->getPractice()->getOrder();
        $getStreamOrder = fn(AssessmentStream $assessmentStream) => $assessmentStream->getStream()->getOrder();

        /** @phpstan-ignore-next-line */
        $sortFunction = fn(AssessmentStream $a, AssessmentStream $b) => $getDelta($b) <=> $getDelta($a) ?: // Note reversed order as we want higher deltas first
            $getBusinessFunctionOrder($a) <=> $getBusinessFunctionOrder($b) ?:
                $getPracticeOrder($a) <=> $getPracticeOrder($b) ?:
                    $getStreamOrder($a) <=> $getStreamOrder($b) ?:
                        0;

        usort(
            $assessmentStreams,
            $sortFunction
        );

        return $assessmentStreams;
    }

    // TODO cleanup
    public function getActiveStreams(Assessment $assessment, bool $sorted = false): array
    {
        return AssessmentStreamFilterService::getActiveStreams($assessment, $sorted); // TODO
    }

    public function getProgress(Assessment $assessment = null, int $savedAnswers = null): float
    {
        // TODO: Maybe use the number of assessmentStreams with status new instead of counting the answers.
        //  We could also store the status of the assessment as this is useless most of the time and can be skipped

        /** @var ItemInterface $cache */
        $cache = $this->redisCache->getItem(Constants::ASSESSMENT_PROGRESS_KEY_PREFIX_ACTIVE.$assessment->getId());
        if (!$cache->isHit()) {
            $savedAnswers = $savedAnswers ?? count($this->assessmentAnswersService->getAssessmentAnswers($assessment));
            $metamodel = $assessment?->getProject()?->getMetamodel() ?? $this->metamodelService->getSAMM();
            $totalQuestions = count($this->questionRepository->findByMetamodel($metamodel));
            $result = ($savedAnswers / $totalQuestions) * 100;

            $cache->expiresAfter(Constants::DEFAULT_CACHE_EXPIRATION);
            $cache->set($result);
            $cache->tag(Constants::ASSESSMENT_PROGRESS_KEY_PREFIX_ACTIVE.$assessment->getId());
            $this->redisCache->save($cache);
        }

        return $cache->get();
    }

    /**
     * @throws QueueNotOnlineException
     * @throws SubmitStreamException
     */
    private function submitStream(AssessmentStream $assessmentStream, User $user, bool $autoValidate): void
    {
        $savedAnswers = $this->assessmentAnswersService->getAssessmentAnswers($assessmentStream->getAssessment());
        if ($this->questionnaireService->isStreamCompleted($assessmentStream->getStream(), $savedAnswers) === false) {
            throw new SubmitStreamException("Can't submit a stream that is not fully answered");
        }

        $validation = new Validation();
        $validation->setComment($assessmentStream->getLastEvaluationStage()->getComment());
        $this->stageService->addNewStage($assessmentStream, $validation, $user);
        if ($autoValidate) {
            $this->autoValidate($assessmentStream, $user);
        }
    }

    public function submitStreamWithAutoValidateAttempt(AssessmentStream $assessmentStream, User $user): void
    {
        $this->submitStream($assessmentStream, $user, true);
    }

    public function submitStreamWithoutAutoValidateAttempt(AssessmentStream $assessmentStream, User $user): void
    {
        $this->submitStream($assessmentStream, $user, false);
    }

    /**
     * @throws WrongStageException|OptimisticLockException
     */
    public function retractStreamSubmission(AssessmentStream $assessmentStream, User $user): void
    {
        if ($this->assessmentStreamService->canStreamBeRetracted($user, $assessmentStream)) {
            if ($assessmentStream->getStatus() === \App\Enum\AssessmentStatus::VALIDATED) {
                $this->undoAutoValidation($assessmentStream, $user);
            } else {
                $this->validateStream($assessmentStream, $user, null, \App\Enum\ValidationStatus::RETRACTED);
            }
            $this->entityManager->persist($assessmentStream);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws WrongStageException|OptimisticLockException
     * @throws InvalidArgumentException
     */
    public function undoValidation(AssessmentStream $assessmentStream): void
    {
        $this->eventDispatcher->dispatch(new PreUndoValidationEvent($assessmentStream));

        $improvement = $assessmentStream->getCurrentStage();
        if (!($improvement instanceof Improvement)) {
            throw new WrongStageException();
        }

        $oldImprovementStatus = $improvement->getStatus();

        $this->stageRepository->trash($improvement);

        $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_VALIDATED.$assessmentStream->getAssessment()->getId()]);
        $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_VALIDATED.'detailed-'.$assessmentStream->getAssessment()->getId()]);

        $assessmentStream->getLastValidationStage()->setStatus(\App\Enum\ValidationStatus::NEW);
        $assessmentStream->getLastValidationStage()->setCompletedAt(null);
        $assessmentStream->setStatus(AssessmentStatus::IN_VALIDATION);
        $this->assessmentStreamService->setScore($assessmentStream);

        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostUndoValidationStreamEvent($assessmentStream, $oldImprovementStatus));
    }

    /**
     * @throws WrongStageException|OptimisticLockException
     */
    private function undoAutoValidation(AssessmentStream $assessmentStream, User $user): void
    {
        $this->eventDispatcher->dispatch(new PreUndoAutoValidationEvent($assessmentStream));

        $improvement = $assessmentStream->getCurrentStage();

        if (!($improvement instanceof Improvement)) {
            throw new WrongStageException();
        }

        $oldImprovementStatus = $improvement->getStatus();

        $assessmentStream->getLastValidationStage()->setStatus(\App\Enum\ValidationStatus::RETRACTED);
        $this->stageRepository->trash($improvement);

        $newStage = new Evaluation();
        $oldEvaluation = $assessmentStream->getLastEvaluationStage();
        $this->assignmentService->addAssignment(new Assignment(), $newStage, $assessmentStream->getLastEvaluationStage()->getSubmittedBy(), $user);

        $this->eventDispatcher->dispatch(new PostUndoAutoValidationEvent($assessmentStream, $oldImprovementStatus));

        $this->stageService->addNewStage($assessmentStream, $newStage, $user);
        $this->stageService->copyStageAnswers($oldEvaluation, $newStage);
        $this->assessmentStreamService->setScore($assessmentStream);

        $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_VALIDATED.$assessmentStream->getAssessment()->getId()]);
        $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_VALIDATED.'detailed-'.$assessmentStream->getAssessment()->getId()]);
        $this->redisCache->invalidateTags([Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX.$assessmentStream->getAssessment()->getId()]);
    }

    private function autoValidate(AssessmentStream $assessmentStream, User $user): void
    {
        $streamScore = array_sum(
            array_map(
                fn($assessmentAnswer) => $assessmentAnswer->getType() === \App\Enum\AssessmentAnswerType::CURRENT ? $assessmentAnswer->getAnswer()->getValue() : 0,
                $assessmentStream->getLastEvaluationStage()->getStageAssessmentAnswers()->getValues()
            )
        );

        $threshold = $assessmentStream->getAssessment()?->getProject()?->getValidationThreshold() ?? 0;

        if ($streamScore <= $threshold) {
            $this->validateStream($assessmentStream, $user, null, \App\Enum\ValidationStatus::AUTO_ACCEPTED);
        }
    }

    /**
     * @throws WrongStageException
     */
    public function validateStream(
        AssessmentStream $assessmentStream,
        User $user,
        string $remark = null,
        ValidationStatus $status = \App\Enum\ValidationStatus::ACCEPTED
    ): void {
        $validation = $assessmentStream->getCurrentStage();
        if (!($validation instanceof Validation)) {
            throw new WrongStageException();
        }

        $validation->setComment($remark);
        $validation->setStatus($status);

        if ($status === \App\Enum\ValidationStatus::REJECTED || $status === \App\Enum\ValidationStatus::RETRACTED) {
            $newEvaluation = new Evaluation();
            $oldEvaluation = $assessmentStream->getLastEvaluationStage();
            $this->assignmentService->addAssignment(new Assignment(), $newEvaluation, $oldEvaluation->getSubmittedBy(), $user);
            $submitter = $status !== \App\Enum\ValidationStatus::RETRACTED ? $user : null;
            $this->stageService->addNewStage($assessmentStream, $newEvaluation, $submitter);
            $this->stageService->copyStageAnswers($oldEvaluation, $newEvaluation);
        } else {
            $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_VALIDATED.$assessmentStream->getAssessment()->getId()]);
            $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_VALIDATED.'detailed-'.$assessmentStream->getAssessment()->getId()]);
            $newImprovement = new Improvement();
            $submitter = $status !== \App\Enum\ValidationStatus::AUTO_ACCEPTED ? $user : null;
            $this->stageService->addNewStage($assessmentStream, $newImprovement, $submitter);
            $this->assessmentStreamService->setScore($assessmentStream);
        }

        $this->entityManager->flush();
    }

    public function canEditValidation(User $user, AssessmentStream $assessmentStream): bool
    {
        return $assessmentStream->getStatus() === \App\Enum\AssessmentStatus::VALIDATED
            && $assessmentStream->getLastValidationStage()->getSubmittedBy() === $user
            && in_array(Role::VALIDATOR->string(), $user->getRoles(), true);
    }

    public function editValidation(AssessmentStream $assessmentStream, ?string $remark): void
    {
        $validationStage = $assessmentStream->getLastValidationStage();
        if (!($validationStage instanceof Validation)) {
            throw new WrongStageException();
        }
        $validationStage->setComment($remark);
        $this->entityManager->flush();
    }

    private function createSnapshot(AssessmentStream $assessmentStream): AssessmentStream
    {
        $clone = $assessmentStream->getCopy();
        $clone->setExpirationDate(null);
        $clone->setStatus(\App\Enum\AssessmentStatus::NEW);
        $this->entityManager->persist($clone);

        // Create evaluation stage
        $newEvaluation = new Evaluation();
        $this->stageService->addNewStage($clone, $newEvaluation);

        // copy the previous evaluation answers
        $this->stageService->copyStageAnswers($assessmentStream->getLastEvaluationStage(), $newEvaluation);

        return $clone;
    }

    public function getUserAccess(AssessmentStream $assessmentStream, User $user): bool
    {
        return match ($assessmentStream->getStatus()) {
            \App\Enum\AssessmentStatus::NEW, \App\Enum\AssessmentStatus::IN_EVALUATION => in_array(Role::EVALUATOR->string(), $user->getRoles(), true)
        ,
            \App\Enum\AssessmentStatus::IN_IMPROVEMENT, \App\Enum\AssessmentStatus::COMPLETE, \App\Enum\AssessmentStatus::VALIDATED => in_array(Role::IMPROVER->string(), $user->getRoles(), true)
        ,
            \App\Enum\AssessmentStatus::IN_VALIDATION => (
                in_array(Role::VALIDATOR->string(), $user->getRoles(), true)
                && ($user !== $assessmentStream->getSubmittedBy() || in_array(Role::MANAGER->string(), $user->getRoles(), true) || in_array(Role::AUDITOR->string(), $user->getRoles(), true))
            ),
            default => false,
        };
    }

    /**
     * @throws NonUniqueResultException|QueueNotOnlineException
     * @throws \Exception
     */
    public function startImprovementStream(Improvement $improvement, \DateTime $targetDate, string $plan, array $newDesiredAnswers, User $user): void
    {
        $this->eventDispatcher->dispatch(new PreStartImprovementStreamEvent($improvement->getAssessmentStream()));

        $improvement->getAssessmentStream()->setStatus(\App\Enum\AssessmentStatus::IN_IMPROVEMENT);

        $improvement->setTargetDate($targetDate);
        $improvement->setPlan($plan);

        if ($this->assignmentService->getStageAssignment($improvement) === null) {
            $this->assignmentService->addAssignment(new Assignment(), $improvement, $user, $user);
        }

        $oldImprovementStatus = $improvement->getStatus();
        $improvement->setStatus(ImprovementStatus::IMPROVE);

        $this->entityManager->flush();

        $this->saveImprovementDesiredAnswers($improvement, $user, $newDesiredAnswers);
        $this->stageService->overtakeAssignments($improvement, $user);

        $this->eventDispatcher->dispatch(new PostStartImprovementStreamEvent($improvement->getAssessmentStream(), $oldImprovementStatus));
    }

    /**
     * @throws QueueNotOnlineException
     * @throws \Exception
     */
    public function completeImprovementStream(Improvement $improvement): void
    {
        $this->eventDispatcher->dispatch(new PreCompleteImprovementStreamEvent($improvement->getAssessmentStream()));

        $oldImprovementStatus = $improvement->getStatus();
        $improvement->getAssessmentStream()->setStatus(\App\Enum\AssessmentStatus::COMPLETE);
        $improvement->setStatus(ImprovementStatus::WONT_IMPROVE);

        $assignment = $this->assignmentService->getStageAssignment($improvement);
        if ($assignment !== null) {
            $this->assignmentService->deleteAssignment($assignment);
        }

        $this->assessmentAnswersService->deleteDesiredAnswersForImprovement($improvement);

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostCompleteImprovementStreamEvent($improvement->getAssessmentStream(), $oldImprovementStatus));
    }

    /**
     * @throws QueueNotOnlineException
     * @throws \Exception
     */
    public function finishImprovementStream(Improvement $improvement, User $user): void
    {
        $assessmentStream = $improvement->getAssessmentStream();

        $this->eventDispatcher->dispatch(new PreFinishImprovementStreamEvent($assessmentStream));

        $clone = $this->createSnapshot($assessmentStream);
        $improvement->setNew($clone);
        $improvement->setCompletedAt(new \DateTime('NOW'));
        $improvement->setSubmittedBy($user);
        $this->assignmentService->completeStageAssignments($assessmentStream->getCurrentStage());
        $assessmentStream->setStatus(\App\Enum\AssessmentStatus::ARCHIVED);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostFinishImprovementStreamEvent($assessmentStream));
    }

    /**
     * @throws QueueNotOnlineException
     * @throws \Exception
     */
    public function cancelImprovementStream(Improvement $improvement, User $user): void
    {
        $assessmentStream = $improvement->getAssessmentStream();

        $this->eventDispatcher->dispatch(new PreCancelImprovementStreamEvent($assessmentStream));

        $oldAssessmentStreamStatus = $assessmentStream->getStatus();
        $assessmentStream->setStatus(\App\Enum\AssessmentStatus::VALIDATED);
        $oldImprovementStatus = $improvement->getStatus();
        $improvement->setStatus(ImprovementStatus::NEW);
        $improvement->setPlan('');
        $desiredAnswers = $this->assessmentAnswersService->getLatestAnswersByAssessmentStreams([$assessmentStream], AssessmentAnswerType::DESIRED);
        foreach ($desiredAnswers as $answer) {
            $this->entityManager->remove($answer);
        }
        $this->stageService->overtakeAssignments($improvement, $user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostCancelImprovementStreamEvent($improvement->getAssessmentStream(), $oldImprovementStatus, $oldAssessmentStreamStatus));
    }

    /**
     * @throws QueueNotOnlineException
     * @throws \Exception
     */
    public function reactivateImprovementStream(Improvement $improvement): void
    {
        $assessmentStream = $improvement->getAssessmentStream();
        $this->eventDispatcher->dispatch(new PreReactivateImprovementStreamEvent($assessmentStream));

        $oldImprovementStatus = $improvement->getStatus();
        $improvement->setStatus(\App\Enum\ImprovementStatus::NEW);
        $assessmentStream->setStatus(\App\Enum\AssessmentStatus::VALIDATED);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostReactivateImprovementStreamEvent($assessmentStream, $oldImprovementStatus));
    }

    /**
     * @throws NonUniqueResultException
     * @throws SavePlanOnIncorrectStreamException
     */
    public function saveImprovementPlan(Improvement $improvement, \DateTime $targetDate, ?string $plan, array $newDesiredAnswers, User $user): void
    {
        if ($improvement->getStatus() !== \App\Enum\ImprovementStatus::NEW && $improvement->getStatus() !== \App\Enum\ImprovementStatus::DRAFT) {
            throw new SavePlanOnIncorrectStreamException();
        }
        $improvement->setStatus(ImprovementStatus::DRAFT);
        $improvement->setTargetDate($targetDate);
        $improvement->setPlan($plan);
        $this->entityManager->flush();
        $this->saveImprovementDesiredAnswers($improvement, $user, $newDesiredAnswers, addAssignment: false);
    }

    /**
     * @throws NonUniqueResultException
     */
    private function saveImprovementDesiredAnswers(Improvement $improvement, User $user, array $newDesiredAnswers, bool $addAssignment = true): void
    {
        foreach ($newDesiredAnswers as $questionId => $newDesiredAnswer) {
            $answer = $this->entityManager->getReference(Answer::class, $newDesiredAnswer);
            $question = $this->entityManager->getReference(Question::class, $questionId);
            $this->assessmentAnswersService->saveAnswer(
                $improvement->getAssessmentStream(),
                $question,
                $answer,
                $user,
                \App\Enum\AssessmentAnswerType::DESIRED,
                $addAssignment
            );
        }
    }

    // TODO cleanup
    public function getValidatedAssessmentStreamsByDate(?Assessment $assessment, \DateTime $dateTime): array
    {
        return $this->assessmentStreamFilterService->getValidatedAssessmentStreamsByDate($assessment, $dateTime); // TODO
    }

    // TODO cleanup
    public function getAssessmentStreamsByDate(?Assessment $assessment, \DateTime $dateTime): array
    {
        return $this->assessmentStreamFilterService->getAssessmentStreamsByDate($assessment, $dateTime); // TODO
    }

    public function getValidatedAssessmentStreams(\DateTime $dateTime, Project ...$projects): array
    {
        $assessments = [];
        foreach ($projects as $project) {
            $assessments[] = $project->getAssessment();
        }

        return $this->assessmentStreamRepository->findLatestValidatedByDateAndAssessments($dateTime, ...$assessments);
    }

    /**
     * @throws SaveRemarkOnIncorrectStreamException
     */
    public function saveValidationRemark(AssessmentStream $assessmentStream, ?string $comment, User $user): void
    {
        $validation = $assessmentStream->getCurrentStage();
        if (!($validation instanceof Validation) || $validation->getStatus() !== \App\Enum\ValidationStatus::NEW) {
            throw new SaveRemarkOnIncorrectStreamException();
        }
        $validation->setComment($comment);
        $this->stageService->overtakeAssignments($validation, $user);
        $this->entityManager->flush();
    }

    /**
     * @throws SaveRemarkOnIncorrectStreamException
     */
    public function saveEvaluationRemark(AssessmentStream $assessmentStream, ?string $comment, User $user): void
    {
        $lastStage = $assessmentStream->getCurrentStage();
        if (($lastStage instanceof Improvement) || ($lastStage instanceof Validation && $lastStage->getStatus() !== \App\Enum\ValidationStatus::NEW)) {
            throw new SaveRemarkOnIncorrectStreamException();
        }
        if ($lastStage === null) {
            $this->stageService->addNewStage($assessmentStream, new Evaluation(), $user);
        }
        $evaluation = $assessmentStream->getLastEvaluationStage();
        $evaluation->setComment($comment);
        $this->entityManager->flush();
    }
}
