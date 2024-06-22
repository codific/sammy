<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Answer;
use App\Entity\Assessment;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\BusinessFunction;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Practice;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Enum\AssessmentAnswerType;
use App\Enum\AssessmentStatus;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentStreamRepository;
use App\Repository\ImprovementRepository;
use App\Repository\QuestionRepository;
use App\Service\AssessmentAnswersService;
use App\Service\AssignmentService;
use App\Service\StageService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\builders\UserBuilder;
use App\Utils\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AssessmentAnswersServiceTest extends AbstractKernelTestCase
{
    private AssessmentAnswersService $assessmentAnswersService;
    private Question $sammQuestion;

    public function setUp(): void
    {
        parent::setUp();
        $this->assessmentAnswersService = $this->getContainer()->get(AssessmentAnswersService::class);
        $this->sammQuestion = $this->getObjectOfClass(Question::class, 1);
    }

    /**
     * @dataProvider testGetAssessmentAnswersProvider
     * @testdox $_dataName
     */
    public function testGetAssessmentAnswers(array $assessmentStreams, array $assessmentAnswers, array $assessmentAnswerType, int $expectedCount)
    {
        $assessment = (new Assessment());
        $question = $this->sammQuestion;

        foreach ($assessmentStreams as $assessmentStream) {
            $assessmentStream->setAssessment($assessment)->setStream($question->getActivity()->getStream());
            $assessmentStream->getAssessmentStreamStages()[0]->setAssessmentStream($assessmentStream);

            $assessment->addAssessmentAssessmentStream($assessmentStream);
            $this->entityManager->persist($assessmentStream);
        }

        for ($i = 0, $iMax = count($assessmentAnswers); $i < $iMax; $i++) {
            $assessmentAnswers[$i]->setQuestion($question)->setAnswer($question->getAnswerSet()->getAnswerSetAnswers()->get(1));
            $assessmentAnswers[$i]->setStage($assessmentStreams[$i]->getAssessmentStreamStages()[0]);
            $assessmentAnswers[$i]->getStage()->addStageAssessmentAnswer($assessmentAnswers[$i]);
            $this->entityManager->persist($assessmentAnswers[$i]);
        }

        $this->entityManager->persist($assessment);
        $this->entityManager->flush();

        foreach ($assessmentAnswerType as $type) {
            $result = $this->assessmentAnswersService->getAssessmentAnswers($assessment, $type);
            self::assertCount($expectedCount, $result);
        }
    }

    private function testGetAssessmentAnswersProvider(): array
    {
        return [
            'Test that the right amount of assessment answers are found by getAssessmentAnswers' => [
                [
                    (new AssessmentStream())->addAssessmentStreamStage(new Evaluation()),
                    (new AssessmentStream())->addAssessmentStreamStage(new Improvement())->setStatus(AssessmentStatus::IN_IMPROVEMENT),
                ],
                [
                    (new AssessmentAnswer()),
                    (new AssessmentAnswer()),
                ],
                [
                    AssessmentAnswerType::CURRENT,
                    AssessmentAnswerType::DESIRED,
                ],
                1, // expected count
            ],
        ];
    }

    /**
     * @dataProvider testGetLatestAnswersByAssessmentStreamsProvider
     * @testdox $_dataName
     */
    public function testGetLatestAnswersByAssessmentStreams(array $assessmentStreams, array $assessmentAnswers, array $assessmentAnswerType, int $expectedCount)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $assessmentStream->setStream($this->sammQuestion->getActivity()->getStream());
            $assessmentStream->getAssessmentStreamStages()[0]->setAssessmentStream($assessmentStream);

            $this->entityManager->persist($assessmentStream);
        }

        for ($i = 0, $iMax = count($assessmentAnswers); $i < $iMax; $i++) {
            $assessmentAnswers[$i]->setQuestion($this->sammQuestion)->setAnswer($this->sammQuestion->getAnswerSet()->getAnswerSetAnswers()->get(1));
            $assessmentAnswers[$i]->setStage($assessmentStreams[$i]->getAssessmentStreamStages()[0]);
            $assessmentAnswers[$i]->getStage()->addStageAssessmentAnswer($assessmentAnswers[$i]);
            $this->entityManager->persist($assessmentAnswers[$i]);
        }
        $this->entityManager->flush();

        foreach ($assessmentAnswerType as $type) {
            $result = $this->assessmentAnswersService->getLatestAnswersByAssessmentStreams($assessmentStreams, $type);

            self::assertCount($expectedCount, $result);
        }
    }

    private function testGetLatestAnswersByAssessmentStreamsProvider(): array
    {
        return [
            'Test that the right amount of assessment answers are found by getLatestAnswersByAssessmentStreams' => [
                [
                    (new AssessmentStream())->addAssessmentStreamStage(new Evaluation()),
                    (new AssessmentStream())->addAssessmentStreamStage(new Improvement())->setStatus(AssessmentStatus::IN_IMPROVEMENT),
                ],
                [
                    (new AssessmentAnswer()),
                    (new AssessmentAnswer()),
                ],
                [
                    AssessmentAnswerType::CURRENT,
                    AssessmentAnswerType::DESIRED,
                ],
                1, // expected count
            ],
        ];
    }


//    /**
//     * @dataProvider testGetAssessmentStreamPreviousAnswersProvider
//     * @testdox $_dataName
//     */
//    public function testGetAssessmentStreamPreviousAnswers(array $assessmentStreams, array $assessmentAnswers, array $assessmentAnswersTypes)
//    {
//
//        foreach ($assessmentStreams as $assessmentStream)
//        {
//            $assessmentStream->setStream($this->sammQuestion->getActivity()->getStream());
//            $assessmentStream->getCurrentStage()->setAssessmentStream($assessmentStream);
//            if ($assessmentStream->getCurrentStage() instanceof Improvement) {
//                $assessmentStream->getCurrentStage()->setNew($assessmentStream);
//            }
//            $this->entityManager->persist($assessmentStream);
//        }
//
//        for ($i = 0, $iMax = count($assessmentAnswers); $i < $iMax; $i++) {
//            $assessmentAnswers[$i]->setType($assessmentAnswersTypes[$i]);
//            $assessmentAnswers[$i]->setQuestion($this->sammQuestion)->setAnswer($this->sammQuestion->getAnswerSet()->getAnswerSetAnswers()->get(1));
//            $assessmentAnswers[$i]->setStage($assessmentStreams[$i]->getAssessmentStreamStages()[0]);
//            $assessmentAnswers[$i]->getStage()->addStageAssessmentAnswer($assessmentAnswers[$i]);
//            $this->entityManager->persist($assessmentAnswers[$i]);
//        }
//        $this->entityManager->flush();
//
//        foreach ($assessmentStreams as $assessmentStream) {
//            $result = $this->assessmentAnswersService->getAssessmentStreamPreviousAnswers($assessmentStream);
//
//            for ($i = 0, $iMax = count($assessmentAnswers); $i < $iMax; $i+=2) {
//                if ($result['old'] !== null || $result['desired'] !== null) {
//                    $resultOldAnswer = $result['old'][$assessmentAnswers[$i]->getQuestion()->getId()][$assessmentAnswers[$i]->getQuestion()->getId()];
//                    $resultDesiredAnswer = $result['desired'][$assessmentAnswers[$i+1]->getQuestion()->getId()][$assessmentAnswers[$i+1]->getQuestion()->getId()];
//
//                    self::assertEquals($assessmentAnswers[$i], $resultOldAnswer);
//                    self::assertEquals($assessmentAnswers[$i+1], $resultDesiredAnswer);
//                }
//            }
//        }
//    }
//
//    private function testGetAssessmentStreamPreviousAnswersProvider(): array
//    {
//        $user = new User();
//        return [
//            'Test that the returned answers are correct by getAssessmentStreamPreviousAnswers' => [
//                [
//                    (new AssessmentStream())->addAssessmentStreamStage(new Evaluation()),
//                    (new AssessmentStream())->addAssessmentStreamStage(new Improvement())
//                ],
//                [
//                    (new AssessmentAnswer())->setUser($user),
//                    (new AssessmentAnswer())->setUser($user),
//                ],
//                [
//                    AssessmentAnswerType::CURRENT,
//                    AssessmentAnswerType::DESIRED
//                ]
//            ],
//        ];
//    }

    /**
     * @dataProvider testSaveCheckboxAnswerProvider
     * @testdox $_dataName
     */
    public function testSaveCheckboxAnswer(User $user, AssessmentStream $assessmentStream, array $assessmentAnswers, string $checkboxKey, string $checkboxState, Stage $stage, bool $answersAreInDB)
    {
        foreach ($assessmentAnswers as $assessmentAnswer) {
            $assessmentAnswer
                ->setAnswer($this->sammQuestion->getAnswerSet()->getAnswerSetAnswers()->get(1))
                ->setQuestion($this->sammQuestion);
            if ($answersAreInDB) {
                $assessmentAnswer->setStage($stage);
                $assessmentStream->addAssessmentStreamStage($stage);
            }

            $this->entityManager->persist($assessmentAnswer);
        }
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $checkboxesJsonData = json_encode([
            [
                "key" => $checkboxKey,
                "isChecked" => $checkboxState,
                "questionId" => (string)$this->sammQuestion->getId()
            ]
        ], JSON_THROW_ON_ERROR);

        $this->assessmentAnswersService->saveCheckboxAnswers($user, $assessmentStream, $checkboxesJsonData);

        $result = $this->entityManager->getRepository(AssessmentAnswer::class)->findBy(['question' => $this->sammQuestion, 'stage' => $stage]);

        if ($answersAreInDB) {
            for ($i = 0, $iMax = count($assessmentAnswers); $i < $iMax; $i++) {
                self::assertEquals($assessmentAnswers[$i], $result[$i]);
            }
        } else {
            self::assertCount(0 , $result);
        }
    }

    private function testSaveCheckboxAnswerProvider(): array
    {
        $user = new User();

        return [
            'Positive Test 1 - saving a checkbox answer is properly saved in the database by saveCheckboxAnswer' => [
                $user,
                (new AssessmentStream()),
                [
                    (new AssessmentAnswer())->setUser($user),
                    (new AssessmentAnswer())->setUser($user),
                ],
                "3", // checkbox key
                "true", // checkbox state
                (new Evaluation()), // stage
                true, // answers should be in the database
            ],
            'Positive Test 2 - saving a checkbox answer is properly saved in the database by saveCheckboxAnswer' => [
                $user,
                (new AssessmentStream()),
                [
                    (new AssessmentAnswer())->setUser($user),
                    (new AssessmentAnswer())->setUser($user),
                ],
                "1", // checkbox key
                "false", // checkbox state
                (new Evaluation()), // stage
                true, // answers should be in the database
            ],
            'Negative Test 1 - saving a checkbox answer should not be in the database' => [
                $user,
                (new AssessmentStream()),
                [
                    (new AssessmentAnswer())->setUser($user),
                    (new AssessmentAnswer())->setUser($user),
                ],
                "1", // checkbox key
                "false", // checkbox state
                (new Evaluation()), // stage
                false, // answers should be in the database
            ],
            'Negative Test 2 - saving a checkbox answer should not be in the database' => [
                $user,
                (new AssessmentStream()),
                [
                    (new AssessmentAnswer())->setUser($user),
                    (new AssessmentAnswer())->setUser($user),
                ],
                "1", // checkbox key
                "false", // checkbox state
                (new Evaluation()), // stage
                false, // answers should be in the database
            ],
        ];
    }

    /**
     * @dataProvider testSaveAnswerProvider
     * @testdox $_dataName
     */
    public function testSaveAnswer(User $user, AssessmentAnswer $assessmentAnswer, AssessmentAnswerType $assessmentAnswerType, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(\Exception::class);
        }

        $answer = $this->sammQuestion->getAnswerSet()->getAnswerSetAnswers()->get(1);
        $assessmentAnswer
            ->setAnswer($answer)
            ->setQuestion($this->sammQuestion);

        $assessmentStream = (new AssessmentStream())->setAssessment(new Assessment())->addAssessmentStreamStage(new Evaluation());

        $this->entityManager->persist($user);
        $this->entityManager->persist($assessmentAnswer);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->assessmentAnswersService->saveAnswer($assessmentStream, $this->sammQuestion, $answer, $user, $assessmentAnswerType);

        $result = $this->entityManager->getRepository(AssessmentAnswer::class)->findOneBy(['question' => $this->sammQuestion, 'user' => $user, 'type' => $assessmentAnswerType]);

        self::assertEquals($assessmentAnswer, $result);
    }

    private function testSaveAnswerProvider(): array
    {
        $user = new User();

        return [
            'Positive test 1 saving an answer with current type is properly saved in the database by saveAnswer' => [
                $user,
                (new AssessmentAnswer())->setUser($user),
                AssessmentAnswerType::CURRENT,
                false, // expect exception
            ],
            'Negative test 1 saving an answer with desired type is properly saved in the database by saveAnswer' => [
                $user,
                (new AssessmentAnswer())->setUser($user),
                AssessmentAnswerType::DESIRED,
                true, // expect exception
            ],
        ];
    }

    /**
     * @dataProvider testDeleteDesiredAnswersForImprovementProvider
     * @testdox $_dataName
     */
    public function testDeleteDesiredAnswersForImprovement(Improvement $improvement, AssessmentAnswerType $assessmentAnswerType, bool $expectNull)
    {
        $assessmentStream = (new AssessmentStream())->setAssessment(new Assessment())->setStream($this->sammQuestion->getActivity()->getStream());
        $assessmentAnswer = (new AssessmentAnswer())
            ->setStage($improvement)->setType($assessmentAnswerType)
            ->setQuestion($this->sammQuestion)->setAnswer($this->sammQuestion->getAnswerSet()->getAnswerSetAnswers()->get(1));
        $improvement->setAssessmentStream($assessmentStream);

        $this->entityManager->persist($assessmentAnswer);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($improvement);
        $this->entityManager->flush();
        $a = $this->entityManager->getRepository(AssessmentAnswer::class)->findAll();
        $resultBefore = $this->entityManager->getRepository(AssessmentAnswer::class)->findOneBy(['stage' => $improvement])->getDeletedAt();

        $this->assessmentAnswersService->deleteDesiredAnswersForImprovement($improvement);

        $resultAfter = $this->entityManager->getRepository(AssessmentAnswer::class)->findOneBy(['stage' => $improvement])->getDeletedAt();

        if ($expectNull) {
            self::assertNull($resultBefore);
            self::assertNull($resultAfter);
        } else {
            self::assertNotEquals($resultBefore, $resultAfter);
        }
    }

    private function testDeleteDesiredAnswersForImprovementProvider(): array
    {
        return [
            'Positive test 1 deleting an answer has properly set deletedAt by deleteDesiredAnswersForImprovement' => [
                new Improvement(),
                AssessmentAnswerType::DESIRED,
                false, // expect exception
            ],
            'Negative test 1 deleting an answer has properly set deletedAt by deleteDesiredAnswersForImprovement' => [
                new Improvement(),
                AssessmentAnswerType::CURRENT,
                true, // expect exception
            ],
        ];
    }

    /**
     * @dataProvider testGetAssessmentAnswersCachedProvider
     * @testdox $_dataName
     */
    public function testGetAssessmentAnswersCached(
        AssessmentAnswer $assessmentAnswer,
        AssessmentStream $assessmentStream,
        Evaluation $evaluation,
        Assessment $assessment,
        array $expectedCriteria): void
    {
        $assessment->addAssessmentAssessmentStream($assessmentStream);
        $assessmentStream->setAssessment($assessment);
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $evaluation->setAssessmentStream($assessmentStream);
        $evaluation->addStageAssessmentAnswer($assessmentAnswer);
        $assessmentAnswer->setType(AssessmentAnswerType::CURRENT);
        $assessmentAnswer->setStage($evaluation);
        $this->entityManager->persist($assessmentAnswer);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($evaluation);
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();

        $result = $this->assessmentAnswersService->getAssessmentAnswers($assessment);
        $actualAssessmentAnswer = $result[$assessmentAnswer->getQuestion()->getId()][$assessmentAnswer->getAnswer()->getId()];

        self::assertEquals($expectedCriteria, $actualAssessmentAnswer->getCriteria());
    }

    private function testGetAssessmentAnswersCachedProvider(): array
    {
        return [
            "Test 1 - Test that getAssessmentAnswers returns the correct assessment answer" => [
                (new AssessmentAnswer())
                    ->setType(AssessmentAnswerType::CURRENT)
                    ->setQuestion(new Question())
                    ->setAnswer(new Answer())
                    ->setCriteria(["checkbox_1" => true, "checkbox_2" => false]), // assessment answer
                (new AssessmentStream())->setStream((new Stream())->setPractice((new Practice())->setBusinessFunction(new BusinessFunction()))), // assessment stream
                (new Evaluation()), // evaluation
                (new Assessment), // assessment
                ["checkbox_1" => true, "checkbox_2" => false], // expected criteria
            ],
            "Test 2 - Test that getAssessmentAnswers returns the correct assessment answer" => [
                (new AssessmentAnswer())
                    ->setType(AssessmentAnswerType::CURRENT)
                    ->setQuestion(new Question())
                    ->setAnswer(new Answer())
                    ->setCriteria(["checkbox_1" => false, "checkbox_2" => true]), // assessment answer
                (new AssessmentStream())->setStream((new Stream())->setPractice((new Practice())->setBusinessFunction(new BusinessFunction()))), // assessment stream
                (new Evaluation()), // evaluation
                (new Assessment), // assessment
                ["checkbox_1" => false, "checkbox_2" => true], // expected criteria
            ],
            "Test 3 - Test that getAssessmentAnswers returns the correct assessment answer" => [
                (new AssessmentAnswer())
                    ->setType(AssessmentAnswerType::CURRENT)
                    ->setQuestion(new Question())
                    ->setAnswer(new Answer())
                    ->setCriteria(["checkbox_1" => false, "checkbox_2" => true, "checkbox_3" => false, "checkbox_4" => true]), // assessment answer
                (new AssessmentStream())->setStream((new Stream())->setPractice((new Practice())->setBusinessFunction(new BusinessFunction()))), // assessment stream
                (new Evaluation()), // evaluation
                (new Assessment), // assessment
                ["checkbox_1" => false, "checkbox_2" => true, "checkbox_3" => false, "checkbox_4" => true], // expected criteria
            ]
        ];
    }

    /**
     * @dataProvider testGetAssessmentAnswersInvalidatesCacheProvider
     * @testdox $_dataName
     */
    public function testGetAssessmentAnswersInvalidatesCache(User $user, AssessmentStream $assessmentStream, AssessmentAnswer $assessmentAnswer, Evaluation $evaluation): void
    {
        $assessment = (new Assessment);
        $assessment->addAssessmentAssessmentStream($assessmentStream);
        $assessmentStream->setAssessment($assessment);
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $evaluation->setAssessmentStream($assessmentStream);
        $evaluation->addStageAssessmentAnswer($assessmentAnswer);
        $assessmentAnswer->setType(AssessmentAnswerType::CURRENT);
        $assessmentAnswer->setStage($evaluation);

        $this->entityManager->persist($assessment);
        $this->entityManager->persist($evaluation);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($assessmentAnswer);
        $this->entityManager->flush();

        $redisCache = self::getContainer()->get(TagAwareCacheInterface::class);
        $checkboxesJsonData = '[{"key":0,"isChecked":true,"questionId":"'.$assessmentAnswer->getQuestion()->getId().'"},{"key":1,"isChecked":true,"questionId":"'.$assessmentAnswer->getQuestion()->getId().'"}]';

        $assessmentAnswerService = new AssessmentAnswersService(
                self::getContainer()->get(EntityManagerInterface::class),
                self::getContainer()->get(AssessmentAnswerRepository::class),
                self::getContainer()->get(AssessmentStreamRepository::class),
                self::getContainer()->get(ImprovementRepository::class),
                self::getContainer()->get(AssignmentService::class),
                self::getContainer()->get(StageService::class),
                $redisCache,
                self::getContainer()->get(QuestionRepository::class),
            );

        $cache = $redisCache->getItem(Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessmentStream->getAssessment()->getId());
        $cache->expiresAfter(Constants::DEFAULT_CACHE_EXPIRATION);
        $cache->set(['randomresult']);
        $cache->tag(Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessmentStream->getAssessment()->getId());
        $redisCache->save($cache);

        $manuallySavedCache = $redisCache->getItem(Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessmentStream->getAssessment()->getId());
        self::assertEquals(
            'assessment-answers-'.$assessmentStream->getAssessment()->getId(),
            $manuallySavedCache->getMetadata()['tags'][Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessmentStream->getAssessment()->getId()]
        );

        $result = $assessmentAnswerService->saveCheckboxAnswers($user, $assessmentStream, $checkboxesJsonData);

        $resultCache = $redisCache->getItem(Constants::ASSESSMENT_ANSWERS_CACHE_KEY_PREFIX . $assessmentStream->getAssessment()->getId());
        self::assertNull($resultCache->get());
        self::assertEquals([], $resultCache->getMetadata());
    }

    private function testGetAssessmentAnswersInvalidatesCacheProvider(): array
    {
        return [
            "Test 1 - Test that getAssessmentAnswers invalidates the cache on checkbox choice save" => [
                (new UserBuilder())->build(), // user
                (new AssessmentStream())->setStream((new Stream())->setPractice((new Practice())->setBusinessFunction(new BusinessFunction()))), // assessment stream
                (new AssessmentAnswer())
                    ->setType(AssessmentAnswerType::CURRENT)
                    ->setQuestion(new Question())
                    ->setAnswer(new Answer())
                    ->setCriteria(["checkbox_1" => false, "checkbox_2" => false]), // assessment answer
                (new Evaluation()), // evaluation
            ],
        ];
    }
}