<?php

namespace App\Tests\Service;

use App\Entity\Answer;
use App\Entity\Assessment;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\BusinessFunction;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Practice;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentAnswerType;
use App\Service\StageService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\_support\ReflectionEntityFactoryBuilder;
use Closure;
use ReflectionMethod;


class StageServiceTest extends AbstractKernelTestCase
{

    public function testGetLatestEvaluationStages()
    {
        $assessmentStreams = $this->getTestAssessmentStreams();

        $targetAssessmentStreams = [$assessmentStream1, $assessmentStream2] = [$assessmentStreams[0], $assessmentStreams[1]];

        $targetEvaluations = [$assessmentStream1->getLastEvaluationStage(), $assessmentStream2->getLastEvaluationStage()];

        self::assertEquals($targetEvaluations, StageService::getLatestEvaluationStages($targetAssessmentStreams));
    }

    public function testGetLatestValidationStages()
    {
        $assessmentStreams = $this->getTestAssessmentStreams();

        $targetAssessmentStreams = [$assessmentStream1, $assessmentStream2] = [$assessmentStreams[0], $assessmentStreams[1]];

        $targetValidations = [$assessmentStream1->getLastValidationStage(), $assessmentStream2->getLastValidationStage()];

        self::assertEquals($targetValidations, StageService::getLatestValidationStages($targetAssessmentStreams));
    }

    public function testGetLatestImprovementStages()
    {
        $assessmentStreams = $this->getTestAssessmentStreams();

        $targetAssessmentStreams = [$assessmentStream1, $assessmentStream2] = [$assessmentStreams[0], $assessmentStreams[1]];

        $targetImprovements = [$assessmentStream1->getLastImprovementStage(), $assessmentStream2->getLastImprovementStage()];

        self::assertEquals($targetImprovements, StageService::getLatestImprovementStages($targetAssessmentStreams));
    }

    private function getTestAssessmentStreams(): array
    {
        $assessmentStreamsArray = [
            new AssessmentStream(),
            new AssessmentStream(),
            new AssessmentStream(),
        ];

        foreach ($assessmentStreamsArray as $assessmentStream) {
            $assessmentStream1Stages = [
                $this->getEvaluation($assessmentStream),
                $this->getValidation($assessmentStream),
                $this->getEvaluation($assessmentStream),
                $this->getValidation($assessmentStream),
                $this->getEvaluation($assessmentStream),
                $this->getValidation($assessmentStream),
                $this->getImprovement($assessmentStream),
            ];

            foreach ($assessmentStream1Stages as $stage) {
                $assessmentStream->addAssessmentStreamStage($stage);
            }
        }

        return $assessmentStreamsArray;
    }

    public function testAddNewStage()
    {
        $stageService = self::getContainer()->get(StageService::class);

        $user = new User();
        $assessmentStream = new AssessmentStream();

        $stageStatusPairs = [
            [new Evaluation(), \App\Enum\AssessmentStatus::IN_EVALUATION],
            [new Validation(), \App\Enum\AssessmentStatus::IN_VALIDATION],
            [(new Improvement())->setStatus(\App\Enum\ImprovementStatus::NEW), \App\Enum\AssessmentStatus::VALIDATED],
            [(new Improvement())->setStatus(\App\Enum\ImprovementStatus::IMPROVE), \App\Enum\AssessmentStatus::IN_IMPROVEMENT],
        ];

        foreach ($stageStatusPairs as $key => [$stage, $status]) {
            $previousStage = $assessmentStream->getCurrentStage();
            /** @var Stage $stage */
            $stageService->addNewStage($assessmentStream, $stage, $user);
            $key ? self::assertEquals($user, $previousStage->getSubmittedBy()) : self::assertEquals(null, $previousStage);
            self::assertEquals($stage, $assessmentStream->getLastStageByClass($stage::class));
            self::assertEquals($assessmentStream, $stage->getAssessmentStream());
            self::assertEquals($status, $assessmentStream->getStatus());
        }

    }

    public function testCopyStageAnswers()
    {
        $stageService = self::getContainer()->get(StageService::class);
        $assessmentAnswerRepository = $this->entityManager->getRepository(AssessmentAnswer::class);


        $firstStage = new Evaluation();
        $secondStage = new Evaluation();

        $numberOfQuestions = rand(3, 7);
        $count = 0;
        while ($count++ < $numberOfQuestions) {
            $assessmentAnswer = $this->addAssessmentAnswer($firstStage, new Answer(), new Question());
            $firstStage->addStageAssessmentAnswer($assessmentAnswer);
        }

        $stageService->copyStageAnswers($firstStage, $secondStage);

        $firstStageAnswers = $assessmentAnswerRepository->findBy(['stage' => $firstStage]);
        $secondStageAnswers = $assessmentAnswerRepository->findBy(['stage' => $secondStage]);

        //Sanity check
        self::assertEquals($numberOfQuestions, sizeof($firstStageAnswers));


        $questionAnswerMap = fn(AssessmentAnswer $assessmentAnswer) => [$assessmentAnswer->getQuestion()->getId() => $assessmentAnswer->getAnswer()->getId()];
        $reduceAnswers = fn(array $stageAnswers) => array_reduce(
            $stageAnswers,
            fn(array $accumulator, AssessmentAnswer $assessmentAnswer) => $accumulator + $questionAnswerMap($assessmentAnswer),
            []
        );

        self::assertEquals($numberOfQuestions, sizeof($secondStageAnswers));
        self::assertEmpty(array_intersect($firstStageAnswers, $secondStageAnswers));
        self::assertEquals($reduceAnswers($firstStageAnswers), $reduceAnswers($secondStageAnswers));
    }

    /**
     * @dataProvider testOvertakeAssignmentsProvider
     */
    public function testOvertakeAssignments(Assignment $assignment, User $oldUser, User $newUser, Stage $stage, string $remark)
    {
        $stageService = self::getContainer()->get(StageService::class);

        $assignment = $assignment
            ->setUser($oldUser)
            ->setAssignedBy($oldUser)
            ->setStage($stage)
            ->setRemark($remark);

        $this->entityManager->persist($assignment);
        $this->entityManager->persist($oldUser);
        $this->entityManager->persist($newUser);
        $this->entityManager->persist($stage);
        $this->entityManager->flush();

        $resultBefore = $this->entityManager->getRepository(Assignment::class)->findOneBy(['remark' => $remark, 'user' => $oldUser]);

        self::assertEquals($assignment->getStage(), $resultBefore->getStage());
        self::assertNull($resultBefore->getDeletedAt());

        $this->entityManager->getFilters()->enable('deleted_entity');
        $stageService->overtakeAssignments($stage, $newUser);
        $this->entityManager->getFilters()->disable('deleted_entity');

        $oldAssignmentAfterOvertake = $this->entityManager->getRepository(Assignment::class)->findOneBy(['remark' => $remark, 'user' => $oldUser]);
        self::assertNotNull($oldAssignmentAfterOvertake->getDeletedAt());

        $newAssignmentAfterOvertake = $this->entityManager->getRepository(Assignment::class)->findOneBy(['stage' => $stage, 'user' => $newUser, 'assignedBy' => $newUser]);
        self::assertNotNull($newAssignmentAfterOvertake);
    }

    private function testOvertakeAssignmentsProvider(): array
    {
        return [
            "Test that the old assignment is soft deleted and a new assignment is created by overtakeAssignments" => [
                (new Assignment()), // assignment
                (new User())->setName("Ivan"), // old user
                (new User())->setName("Manol"), // new user
                (new Validation())
                    ->setAssessmentStream(
                        (new AssessmentStream())
                            ->setAssessment(
                                (new Assessment())
                                    ->setProject((new Project()))
                            )
                            ->setStream(
                                (new Stream())
                                    ->setPractice(
                                        (new Practice())
                                            ->setBusinessFunction(new BusinessFunction())
                                    )
                            )
                    ), // stage
                "this is a random remark ".bin2hex(random_bytes(5)), // remark
            ],
        ];
    }

    /**
     * @dataProvider testCopyStageAnswersDeletedFilterProvider
     * @testdox $_dataName
     */
    public function testCopyStageAnswersDeletedFilter(Stage $sourceStage, Stage $destinationStage)
    {
        $this->entityManager->persist($sourceStage);
        $this->entityManager->persist($destinationStage);
        $this->entityManager->flush();

        $stageService = self::getContainer()->get(StageService::class);

        for ($i = 0; $i < 3; $i++) {
            $sourceStage->addStageAssessmentAnswer(
                (new AssessmentAnswer())
                    ->setStage($sourceStage)
                    ->setAnswer((new Answer())->setText("answer ".bin2hex(random_bytes(5))))
                    ->setQuestion((new Question())->setText("question ".bin2hex(random_bytes(5))))
            );
        }

        $destinationAssessmentAnswersBefore = $this->entityManager->getRepository(AssessmentAnswer::class)->findBy(['stage' => $destinationStage]);
        self::assertEmpty($destinationAssessmentAnswersBefore);

        $stageService->copyStageAnswers($sourceStage, $destinationStage);

        $destinationAssessmentAnswersAfter = $this->entityManager->getRepository(AssessmentAnswer::class)->findBy(['stage' => $destinationStage]);

        self::assertCount(count($sourceStage->getStageAssessmentAnswers()), $destinationAssessmentAnswersAfter);
        foreach ($destinationAssessmentAnswersAfter as $index => $assessmentAnswer) {
            self::assertEquals($sourceStage->getStageAssessmentAnswers()->get($index)->getAnswer()->getText(), $assessmentAnswer->getAnswer()->getText());
            self::assertEquals($sourceStage->getStageAssessmentAnswers()->get($index)->getQuestion()->getText(), $assessmentAnswer->getQuestion()->getText());
        }
    }

    private function testCopyStageAnswersDeletedFilterProvider(): array
    {
        return [
            "Test that the the assessment answers are copied from the source to the destination stage by copyStageAnswers" => [
                (new Evaluation()), // source stage
                (new Evaluation()), // destination stage
            ],
        ];
    }

    //Helper functions

    /**
     * This is used to cache factories, instead of rebuilding them every time
     * @var Closure[]
     */
    private array $factories = [];

    private function addAssessmentAnswer(Stage $stage, Answer $answer, Question $question, AssessmentAnswerType $type = \App\Enum\AssessmentAnswerType::CURRENT): AssessmentAnswer
    {
        $assessmentAnswerFactory = $this->factories['baseAssessmentAnswerFactory'] ??=
            ReflectionEntityFactoryBuilder::getEntityFactoryByReflection(AssessmentAnswer::class, new ReflectionMethod(__METHOD__));

        /** @var AssessmentAnswer $assessmentAnswer */
        $assessmentAnswer = $assessmentAnswerFactory(func_get_args());

        $this->entityManager->persist($assessmentAnswer);
        $this->entityManager->flush($assessmentAnswer);

        return $assessmentAnswer;
    }

    /**
     * This function's argument names must match the entity setters
     */
    private function getEvaluation(AssessmentStream $assessmentStream): Evaluation
    {
        $evaluationFactory = $this->factories['baseEvaluationFactory'] ??=
            ReflectionEntityFactoryBuilder::getEntityFactoryByReflection(Evaluation::class, new ReflectionMethod(__METHOD__));

        /** @var Evaluation $evaluation */
        $evaluation = $evaluationFactory(func_get_args());

        return $evaluation;
    }

    /**
     * This function's argument names must match the entity setters
     */
    private function getValidation(AssessmentStream $assessmentStream): Validation
    {
        $validationFactory = $this->factories['baseValidationFactory'] ??=
            ReflectionEntityFactoryBuilder::getEntityFactoryByReflection(Validation::class, new ReflectionMethod(__METHOD__));

        /** @var Validation $validation */
        $validation = $validationFactory(func_get_args());

        return $validation;
    }

    /**
     * This function's argument names must match the entity setters
     */
    private function getImprovement(AssessmentStream $assessmentStream): Improvement
    {
        $improvementFactory = $this->factories['baseImprovementFactory'] ??=
            ReflectionEntityFactoryBuilder::getEntityFactoryByReflection(Improvement::class, new ReflectionMethod(__METHOD__));

        /** @var Improvement $improvement */
        $improvement = $improvementFactory(func_get_args());

        return $improvement;
    }


}