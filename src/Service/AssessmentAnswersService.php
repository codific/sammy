<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Assessment;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Question;
use App\Entity\User;
use App\Enum\AssessmentAnswerType;
use App\Enum\AssessmentStatus;
use App\Exception\QueueNotOnlineException;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentStreamRepository;
use App\Repository\ImprovementRepository;
use App\Repository\QuestionRepository;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AssessmentAnswersService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AssessmentAnswerRepository $assessmentAnswerRepository,
        private readonly AssessmentStreamRepository $assessmentStreamRepository,
        private readonly ImprovementRepository $improvementRepository,
        private readonly AssignmentService $assignmentService,
        private readonly StageService $stageService,
        private readonly TagAwareCacheInterface $redisCache,
        private readonly QuestionRepository $questionRepository,
    ) {
    }

    public static function getSavedAnswersArray(array $rawAnswers): array
    {
        $answers = [];
        foreach ($rawAnswers as $rawAnswer) {
            $question = $rawAnswer->getQuestion();
            $answer = $rawAnswer->getAnswer();
            if ($answer !== null) {
                $answers[$question->getId()][$answer->getId()] = $rawAnswer;
                $answers[$question->getId()]['answerOrder'] = $answer->getOrder();
            }
        }

        return $answers;
    }

    public function getAssessmentAnswers(Assessment $assessment): array
    {
        $cache = $this->redisCache->getItem(Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessment->getId());
        if (!$cache->isHit()) {
            // TODO: refactor this method to use a single query
  //        $assessmentAnswers = $this->assessmentAnswerRepository->findByAssessment($assessment, $type);
            $assessmentStreams = $this->assessmentStreamRepository->findActiveByAssessment($assessment);
            $assessmentAnswers = $this->getLatestAnswersByAssessmentStreams($assessmentStreams);
            $result = self::getSavedAnswersArray($assessmentAnswers);

            $cache->expiresAfter(Constants::DEFAULT_CACHE_EXPIRATION);
            $cache->set($result);
            $cache->tag(Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessment->getId());
            $this->redisCache->save($cache);
        }
        return $cache->get();
    }

    public function getLatestAnswersByAssessmentStreams(array $assessmentStreams, AssessmentAnswerType $type = \App\Enum\AssessmentAnswerType::CURRENT): array
    {
        $stages = match ($type) {
            \App\Enum\AssessmentAnswerType::CURRENT => StageService::getLatestEvaluationStages($assessmentStreams),
            \App\Enum\AssessmentAnswerType::DESIRED => StageService::getLatestImprovementStages($assessmentStreams)
        };

        return $this->assessmentAnswerRepository->findByStages($stages);
    }

    public function getStructuredAssessmentStreamAnswers(AssessmentStream $assessmentStream, AssessmentAnswerType $type = \App\Enum\AssessmentAnswerType::CURRENT): array
    {
        return self::getSavedAnswersArray($this->getLatestAssessmentStreamAnswers($assessmentStream, $type));
    }

    // TODO: reuse getLatestAnswersByAssessmentStreams() method
    public function getLatestAssessmentStreamAnswers(AssessmentStream $assessmentStream, AssessmentAnswerType $type = \App\Enum\AssessmentAnswerType::CURRENT): array
    {
//        return $this->getLatestAnswersByAssessmentStreams([$assessmentStream], $type);
        $stage = match ($type) {
            \App\Enum\AssessmentAnswerType::CURRENT => $assessmentStream->getLastEvaluationStage(),
            \App\Enum\AssessmentAnswerType::DESIRED => $assessmentStream->getLastImprovementStage()
        };

        return $this->assessmentAnswerRepository->findByStageOptimized($stage, $type);
    }

    #[ArrayShape(['old' => 'array', 'desired' => 'array'])]
    public function getAssessmentStreamPreviousAnswers(AssessmentStream $assessmentStream): array
    {
        $oldImprovement = $this->improvementRepository->findOneBy(['new' => $assessmentStream]);

        $oldAssessmentStream = $oldImprovement?->getAssessmentStream();
        if ($oldAssessmentStream !== null) {
            $oldAnswers = $this->getLatestAssessmentStreamAnswers($oldAssessmentStream, \App\Enum\AssessmentAnswerType::CURRENT);
            $oldAnswersArray = self::getSavedAnswersArray($oldAnswers);
            $desiredAnswers = $this->getLatestAssessmentStreamAnswers($oldAssessmentStream, \App\Enum\AssessmentAnswerType::DESIRED);
            $desiredAnswersArray = self::getSavedAnswersArray($desiredAnswers);
        }

        return ['old' => $oldAnswersArray ?? null, 'desired' => $desiredAnswersArray ?? null];
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function saveCheckboxAnswers(User $currentUser, AssessmentStream $assessmentStream, string $checkboxesJsonData): bool
    {
        $checkboxesArray = json_decode($checkboxesJsonData, true, 512, JSON_THROW_ON_ERROR);
        $checkboxesCount = count($checkboxesArray); // count qualities
        $questionId = $checkboxesArray[0]['questionId'];
        $question = $this->questionRepository->findOneBy(['id' => $questionId]);
        $stageClass = match ($assessmentStream->getStatus()) {
            default => Evaluation::class,
            AssessmentStatus::VALIDATED => Improvement::class
        };
        $assessmentAnswer = $this->assessmentAnswerRepository->findOneBy(['question' => $question, 'stage' => $assessmentStream->getLastStageByClass($stageClass)]);

        $this->entityManager->beginTransaction();
        foreach ($checkboxesArray as $currentCheckbox) {
            $checkboxKey = $currentCheckbox['key'];
            $checkboxState = $currentCheckbox['isChecked'];

            // checks if the given key is outside the range of the checkbox keys, in case someone sends a custom payload
            if ($checkboxKey < 0 || $checkboxKey > $checkboxesCount) {
                return false;
            }

            // json_encoding is used to convert it from int and boolean to strings
            $isSuccessfullySaved = $this->saveCheckboxAnswer($assessmentAnswer, $question, json_encode($checkboxKey), json_encode($checkboxState), $currentUser);

            if (!$isSuccessfullySaved) {
                $this->entityManager->rollback();
                return false;
            }
        }
        $this->redisCache->invalidateTags([Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessmentStream->getAssessment()->getId()]);
        $this->entityManager->commit();
        return true;
    }

    /**
     * @throws \Exception
     */
    private function saveCheckboxAnswer(?AssessmentAnswer $assessmentAnswer, ?Question $question, string $checkboxKey, string $checkboxState, User $user): bool
    {
        if ($assessmentAnswer === null || $question === null || !in_array($checkboxState, ['true', 'false'], true)) {
            return false;
        }

        $criteria = $assessmentAnswer->getCriteria();
        $criteria['checkbox_'.$checkboxKey] = $checkboxState;
        $assessmentAnswer->setCriteria($criteria);
        $assessmentAnswer->setUser($user);

        $this->entityManager->flush();

        return true;
    }

    /**
     * @throws QueueNotOnlineException
     * @throws InvalidArgumentException
     */
    public function saveAnswer(
        AssessmentStream $assessmentStream,
        Question $question,
        Answer $answer,
        User $user,
        AssessmentAnswerType $type = \App\Enum\AssessmentAnswerType::CURRENT,
        bool $addAssignment = true
    ): void {
        // TODO this should probably be somewhere else
        if ($assessmentStream->getCurrentStage() === null && $type === \App\Enum\AssessmentAnswerType::CURRENT) {
            $this->stageService->addNewStage($assessmentStream, new Evaluation(), $user);
        }

        $stageClass = match ($type) {
            default => Evaluation::class,
            \App\Enum\AssessmentAnswerType::DESIRED => Improvement::class
        };
        $currentStage = $assessmentStream->getLastStageByClass($stageClass);

        if (!$currentStage instanceof $stageClass) {
            throw new \Exception('Stage is not of appropriate class');
        }

        $assignment = $this->assignmentService->getStageAssignment($currentStage);
        if ($assignment === null && $addAssignment) {
            $this->assignmentService->addAssignment(new Assignment(), $currentStage, $user, $user);
        }

        $assessmentAnswer = $this->assessmentAnswerRepository->findOneBy(['stage' => $currentStage, 'question' => $question, 'type' => $type]);

        if ($assessmentAnswer === null) {
            $assessmentAnswer = new AssessmentAnswer();
            $assessmentAnswer->setStage($currentStage);
            $assessmentAnswer->setQuestion($question);
            $assessmentAnswer->setType($type);
            $this->entityManager->persist($assessmentAnswer);
        }

        $assessmentAnswer->setAnswer($answer);

        $assessmentAnswer->setUser($user);

        $this->entityManager->flush();

        $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_ACTIVE.$assessmentStream->getAssessment()->getId()]);
        $this->redisCache->invalidateTags([Constants::SCORE_KEY_PREFIX_ACTIVE.'detailed-'.$assessmentStream->getAssessment()->getId()]);
        $this->redisCache->invalidateTags([Constants::ASSESSMENT_PROGRESS_KEY_PREFIX_ACTIVE.$assessmentStream->getAssessment()->getId()]);
        if ($type === AssessmentAnswerType::CURRENT) {
            $this->redisCache->invalidateTags([Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX.$assessmentStream->getAssessment()->getId()]);
        }
    }

    public function deleteDesiredAnswersForImprovement(Improvement $improvement): void
    {
        $answers = $this->assessmentAnswerRepository->findByStageOptimized($improvement);
        foreach ($answers as $answer) {
            $this->assessmentAnswerRepository->trash($answer);
        }
    }

    public static function getAnswerTypeByStageClass(string $stageClass): ?AssessmentAnswerType
    {
        return match ($stageClass) {
            Evaluation::class => \App\Enum\AssessmentAnswerType::CURRENT,
            Improvement::class => \App\Enum\AssessmentAnswerType::DESIRED,
            default => null
        };
    }
}
