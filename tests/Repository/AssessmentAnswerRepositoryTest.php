<?php

namespace App\Tests\Repository;

use App\Entity\Abstraction\AbstractEntity;
use App\Entity\Answer;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentAnswerType;
use App\Repository\AssessmentAnswerRepository;
use App\Service\AssessmentAnswersService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\_support\ReflectionEntityFactoryBuilder;
use App\Tests\EntityManagerTestCase;
use Closure;
use ReflectionMethod;

/*
 * This test assumes you have your base Test DB setup
 */

class AssessmentAnswerRepositoryTest extends AbstractKernelTestCase
{
    private AssessmentAnswerRepository $assessmentAnswerRepository;
    private Question $sammQuestion1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentAnswerRepository = $this->getContainer()->get(AssessmentAnswerRepository::class);
        $this->sammQuestion1 = $this->getObjectOfClass(Question::class, 1);
    }

    /**
     * @dataProvider findByStageOptimizedProvider
     */
    public function testFindByStageOptimized(array $allAnswers, Evaluation|Improvement $stage, ?AssessmentAnswerType $type, array $expectedAnswers)
    {
        $this->entityManager->persist($stage);
        $this->entityManager->flush();
        
        $counter = 0;
        /** @var AssessmentAnswer $answer */
        foreach($allAnswers as $answer) {
            $answer->setQuestion($this->sammQuestion1)
                ->setAnswer($this->sammQuestion1->getAnswerSet()->getAnswerSetAnswers()->get(($counter++%4)))
                ->getAssessmentStream()->setStream($this->sammQuestion1->getActivity()->getStream());
            $this->entityManager->persist($answer);
        }

        $this->entityManager->flush();
        $answers = $this->assessmentAnswerRepository->findByStageOptimized($stage, $type);
        self::assertSameSize($answers, $expectedAnswers);
        foreach($answers as $answer) {
            self::assertContains($answer, $expectedAnswers);
        }
    }

    public function findByStageOptimizedProvider(): array
    {

        return [
            "Positive 1: 4 answers in DB, 2 are CURRENT, 1 is DESIRED, 1 doesn't belong to the provided stage, expecting the current ones" => [
                "assessment answers in DB" => [
                    ($answer = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage = new Evaluation())->addStageAssessmentAnswer($answer)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer2 = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage)->addStageAssessmentAnswer($answer2)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer3 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage)->addStageAssessmentAnswer($answer3)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer4 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage2 = new Improvement())->addStageAssessmentAnswer($answer4)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage2))),
                ],
                "stage" => $stage,
                "assessment answer type" => AssessmentAnswerType::CURRENT,
                "expected answers" => [$answer, $answer2],
            ],
            "Positive 2: 4 answers in DB, 2 are CURRENT, 1 is DESIRED, 1 doesn't belong to the provided stage, expecting the desired one" => [
                "assessment answers in DB" => [
                    ($answer = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage = new Evaluation())->addStageAssessmentAnswer($answer)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer2 = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage)->addStageAssessmentAnswer($answer2)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer3 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage)->addStageAssessmentAnswer($answer3)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer4 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage2 = new Improvement())->addStageAssessmentAnswer($answer4)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage2))),
                ],
                "stage" => $stage,
                "assessment answer type" => AssessmentAnswerType::DESIRED,
                "expected answers" => [$answer3],
            ],
//            "Negative 1: 4 answers in DB, 2 are CURRENT, 1 is DESIRED, 1 doesn't belong to the provided stage, stage type is Validation, expecting an empty set" => [
//                "assessment answers in DB" => [
//                    ($answer = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage = new Validation())->addStageAssessmentAnswer($answer)
//                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
//                    ($answer2 = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage)->addStageAssessmentAnswer($answer2)
//                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
//                    ($answer3 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage)->addStageAssessmentAnswer($answer3)
//                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
//                    ($answer4 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage2 = new Validation())->addStageAssessmentAnswer($answer4)
//                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage2))),
//                ],
//                "stage" => $stage,
//                "assessment answer type" => AssessmentAnswerType::CURRENT,
//                "expected answers" => [],
//            ],
        ];
    }

    /**
     * @dataProvider findByStagesProvider
     */
    public function testFindByStages(array $allAnswers, array $stages, array $expectedAnswers)
    {
        foreach ($stages as $stage) {
            $this->entityManager->persist($stage);
        }
        $this->entityManager->flush();

        $counter = 0;
        /** @var AssessmentAnswer $answer */
        foreach($allAnswers as $answer) {
            $answer->setQuestion($this->sammQuestion1)
                ->setAnswer($this->sammQuestion1->getAnswerSet()->getAnswerSetAnswers()->get(($counter++)%4))
                ->getAssessmentStream()->setStream($this->sammQuestion1->getActivity()->getStream());
            $this->entityManager->persist($answer);
        }

        $this->entityManager->flush();
        $answers = $this->assessmentAnswerRepository->findByStages($stages);
        self::assertSameSize($answers, $expectedAnswers);
        foreach($answers as $answer) {
            self::assertContains($answer, $expectedAnswers);
        }
    }

    public function findByStagesProvider(): array
    {
        return [
            "Positive 1: 2 stages with 3/1 answers each, expecting all 4 answers" => [
                "assessment answers in DB" => [
                    ($answer = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage = new Evaluation())->addStageAssessmentAnswer($answer)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer2 = new AssessmentAnswer())->setType(AssessmentAnswerType::CURRENT)->setStage(($stage)->addStageAssessmentAnswer($answer2)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer3 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage)->addStageAssessmentAnswer($answer3)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage))),
                    ($answer4 = new AssessmentAnswer())->setType(AssessmentAnswerType::DESIRED)->setStage(($stage2 = new Improvement())->addStageAssessmentAnswer($answer4)
                        ->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($stage2))),
                ],
                "stage" => [$stage, $stage2],
                "expected answers" => [$answer, $answer2, $answer3, $answer4],
            ],
        ];
    }

    /**
     * @dataProvider findByUserProvider
     */
    public function testFindByUser(array $allAnswers, User $user, array $expectedAnswers)
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $counter = 0;
        /** @var AssessmentAnswer $answer */
        foreach($allAnswers as $answer) {
            $this->entityManager->persist($answer);
        }

        $this->entityManager->flush();
        $answers = $this->assessmentAnswerRepository->findByUser($user);
        self::assertSameSize($answers, $expectedAnswers);
        foreach($answers as $answer) {
            self::assertContains($answer, $expectedAnswers);
        }
    }

    public function findByUserProvider(): array
    {
        return [
            "Positive 1: 3 answers with 2 users, expecting 2 answers" => [
                "assessment answers in DB" => [
                    ($answer = new AssessmentAnswer())->setUser($user = new User()),
                    ($answer2 = new AssessmentAnswer())->setUser($user),
                    ($answer3 = new AssessmentAnswer())->setUser(new User()),
                ],
                "user" => $user,
                "expected answers" => [$answer, $answer2],
            ],
            "Positive 2: 3 answers with 2 users, expecting 0 answers" => [
                "assessment answers in DB" => [
                    ($answer = new AssessmentAnswer())->setUser($user = new User()),
                    ($answer2 = new AssessmentAnswer())->setUser($user),
                    ($answer3 = new AssessmentAnswer())->setUser(new User()),
                ],
                "user" => (new User())->setId(123),
                "expected answers" => [],
            ],
        ];
    }
}
