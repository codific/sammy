<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Stage;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentAnswerType;
use App\Event\Application\Post\PostAddNewStageEvent;
use App\Event\Application\Pre\PreAddNewStageEvent;
use App\Exception\QueueNotOnlineException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StageService
{
    public function __construct(
        private readonly AssignmentService $assignmentService,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * You have to provide the next stage.
     * AssessmentStream is set by this method, you must set all other fields yourself.
     * Current stage is set to completed NOW by the user you pass.
     * @throws QueueNotOnlineException
     * @throws \Exception
     */
    public function addNewStage(AssessmentStream $assessmentStream, Stage $nextStage, ?User $user = null): void
    {
        $stage = $assessmentStream->getCurrentStage();
        $this->eventDispatcher->dispatch(new PreAddNewStageEvent($assessmentStream));

        if ($stage !== null) {
            $stage->setSubmittedBy($user);
            $stage->setCompletedAt(new \DateTime('NOW'));

            $this->overtakeAssignments($stage, $user);

            $this->assignmentService->completeStageAssignments($stage);
        }

        $nextStage->setAssessmentStream($assessmentStream);
        $assessmentStream->addAssessmentStreamStage($nextStage);
        $assessmentStream->setStatusByStage($nextStage);

        $this->entityManager->persist($nextStage);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostAddNewStageEvent($assessmentStream));
    }

    public function copyStageAnswers(Stage $source, Stage $destination, AssessmentAnswerType $type = \App\Enum\AssessmentAnswerType::CURRENT): void
    {
        $answers = $source->getStageAssessmentAnswers();

        $shouldEnableFilter = false;
        if ($this->entityManager->getFilters()->isEnabled('deleted_entity')) {
            $shouldEnableFilter = true;
            $this->entityManager->getFilters()->disable('deleted_entity');
        }
        foreach ($answers as $answer) {
            if ($answer->getType() === $type) {
                $answerClone = $answer->getCopy();
                $answerClone->setStage($destination);
                $this->entityManager->persist($answerClone);
            }
        }
        $this->entityManager->flush();
        if ($shouldEnableFilter) {
            $this->entityManager->getFilters()->enable('deleted_entity');
        }
    }

    /**
     * @param AssessmentStream[] $assessmentStreams
     *
     * @return Evaluation[]
     */
    public static function getLatestEvaluationStages(array $assessmentStreams): array
    {
        return self::getLatestStagesOfClassFunction(Evaluation::class)($assessmentStreams);
    }

    /**
     * @param AssessmentStream[] $assessmentStreams
     *
     * @return Validation[]
     */
    public static function getLatestValidationStages(array $assessmentStreams): array
    {
        return self::getLatestStagesOfClassFunction(Validation::class)($assessmentStreams);
    }

    /**
     * @param AssessmentStream[] $assessmentStreams
     *
     * @return Improvement[]
     */
    public static function getLatestImprovementStages(array $assessmentStreams): array
    {
        return self::getLatestStagesOfClassFunction(Improvement::class)($assessmentStreams);
    }

    private static function getLatestStagesOfClassFunction($class): \Closure
    {
        return function ($assessmentStreams) use ($class): array {
            return array_map(
                fn(AssessmentStream $assessmentStream) => $assessmentStream->getLastStageByClass($class),
                $assessmentStreams
            );
        };
    }

    public function overtakeAssignments(Stage $stage, ?User $user): void
    {
        $users = $this->assignmentService->deleteStageAssignments($stage, $user);
        if ($user !== null) {
            if ($this->assignmentService->getStageAssignment($stage) === null) {
                $this->assignmentService->addAssignment(new Assignment(), $stage, $user, $user);
            }
        }
    }
}
