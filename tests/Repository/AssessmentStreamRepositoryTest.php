<?php

namespace App\Tests\Repository;

use App\Entity\Abstraction\AbstractEntity;
use App\Entity\Answer;
use App\Entity\Assessment;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentAnswerType;
use App\Enum\AssessmentStatus;
use App\Enum\ValidationStatus;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentStreamRepository;
use App\Service\AssessmentAnswersService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\_support\ReflectionEntityFactoryBuilder;
use App\Tests\EntityManagerTestCase;
use Closure;
use ReflectionMethod;
use Symfony\Bundle\MakerBundle\Str;

/*
 * This test assumes you have your base Test DB setup
 */

class AssessmentStreamRepositoryTest extends AbstractKernelTestCase
{
    private AssessmentStreamRepository $assessmentStreamRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessmentStreamRepository = self::getContainer()->get(AssessmentStreamRepository::class);
    }

    /**
     * @dataProvider findAllStreamsForAssessmentProvider
     */
    public function testFindAllStreamsForAssessment(array $assessmentStreams, Assessment $assessment, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $assessmentsFromDb = $this->assessmentStreamRepository->findAllStreamsForAssessment($assessment);
        self::assertSameSize($assessmentsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findAllStreamsForAssessmentProvider(): array
    {
        return [
            "Positive 1: 3 assessmentStreams linked to 3 assessments, expecting 1 assessmentStream" => [
                "all assessment streams" => [
                    (new AssessmentStream())->setStream(new Stream())->addAssessmentStreamStage(new Evaluation())->setAssessment(new Assessment()),
                    ($stream2 = new AssessmentStream())->setStream(new Stream())->addAssessmentStreamStage(new Evaluation())->setAssessment($assessment = new Assessment()),
                    (new AssessmentStream())->setStream(new Stream())->addAssessmentStreamStage(new Evaluation())->setAssessment(new Assessment()),
                ],
                "assessment" => $assessment,
                "expected assessment streams" => [
                    $stream2
                ]
            ],
            "Positive 2: 3 assessmentStreams all linked to 1 assessment, expecting 3 assessment streams" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStream(new Stream())->addAssessmentStreamStage(new Evaluation())->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStream(new Stream())->addAssessmentStreamStage(new Evaluation())->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStream(new Stream())->addAssessmentStreamStage(new Evaluation())->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "expected assessment streams" => [
                    $stream1, $stream2, $stream3
                ]
            ],
        ];
    }

    /**
     * @dataProvider findValidatedByAssessmentProvider
     */
    public function testFindValidatedByAssessment(array $assessmentStreams, Assessment $assessment, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $assessmentsFromDb = $this->assessmentStreamRepository->findValidatedByAssessment($assessment);
        self::assertSameSize($assessmentsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findValidatedByAssessmentProvider(): array
    {
        return [
            "Positive 1: 3 assessmentStreams linked to 1 assessment, only 2 validated, expecting 2 assessment streams" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "expected assessment streams" => [
                    $stream1, $stream2
                ]
            ],
            "Positive 2: 3 assessmentStreams linked to 1 assessment, none is validated" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::NEW)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::IN_EVALUATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "expected assessment streams" => []
            ]
        ];
    }

    /**
     * @dataProvider findLatestValidatedByAssessmentAndDateProvider
     */
    public function testFindLatestValidatedByAssessmentAndDate(array $assessmentStreams, Assessment $assessment, \DateTime $date, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $assessmentsFromDb = $this->assessmentStreamRepository->findLatestValidatedByAssessmentAndDate($assessment, $date);
        self::assertSameSize($assessmentsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findLatestValidatedByAssessmentAndDateProvider(): array
    {
        return [
            "Positive 1: 5 streams only 2 are validated and has a validation before the given date that has a status of accepted" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Validation())->setStatus(ValidationStatus::ACCEPTED)->setCompletedAt(new \DateTime("-2 day"))
                            ->setAssessmentStream($stream1))->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage((new Validation())->setStatus(ValidationStatus::AUTO_ACCEPTED)->setCompletedAt(new \DateTime("+ 1day"))
                            ->setAssessmentStream($stream2))->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Validation())->setStatus(ValidationStatus::REJECTED)->setCompletedAt(new \DateTime("+3 day"))
                            ->setAssessmentStream($stream3))->setAssessment($assessment),
                    ($stream4 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Validation())->setStatus(ValidationStatus::AUTO_ACCEPTED)->setCompletedAt(new \DateTime("+4 day"))
                            ->setAssessmentStream($stream4))->setAssessment($assessment),
                    ($stream5 = new AssessmentStream())->setStatus(AssessmentStatus::IN_IMPROVEMENT)
                        ->addAssessmentStreamStage((new Validation())->setStatus(ValidationStatus::ACCEPTED)->setCompletedAt(new \DateTime("-1 day"))
                            ->setAssessmentStream($stream5))->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "date" => new \DateTime(),
                "expected assessment streams" => [
                    $stream5
                ]
            ]
        ];
    }

    /**
     * @dataProvider findLatestByAssessmentAndDateProvider
     */
    public function testFindLatestByAssessmentAndDate(array $assessmentStreams, Assessment $assessment, \DateTime $date, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $assessmentsFromDb = $this->assessmentStreamRepository->findLatestByAssessmentAndDate($assessment, $date);
        self::assertSameSize($assessmentsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findLatestByAssessmentAndDateProvider(): array
    {
        return [
            "Positive 1: 4 all should match the condition, the last is selected as it will have the highest id" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))
                        ->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))
                        ->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))
                        ->setAssessment($assessment),
                    ($stream4 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream4))
                        ->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "date" => new \DateTime("+2 day"),
                "expected assessment streams" => [
                    $stream4
                ]
            ],
            "Positive 2: date time provided shouldn't match any of the results" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))
                        ->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))
                        ->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))
                        ->setAssessment($assessment),
                    ($stream4 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream4))
                        ->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "date" => new \DateTime("-2 day"),
                "expected assessment streams" => []
            ]
        ];
    }


    /**
     * @dataProvider findLatestByAssessmentsProvider
     */
    public function testFindLatestByAssessments(array $assessmentStreams, array $assessments, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        foreach ($assessments as $assessment) {
            $this->entityManager->persist($assessment);
        }
        $this->entityManager->flush();
        $assessmentStreamsFromDb = $this->assessmentStreamRepository->findLatestByAssessments(...$assessments);
        self::assertSameSize($assessmentStreamsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentStreamsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findLatestByAssessmentsProvider(): array
    {
        return [
            "Positive 1: 2 assessments, 4 assessment streams, expecting 2 streams with highest ids" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))
                        ->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))
                        ->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))
                        ->setAssessment($assessment2 = new Assessment()),
                    ($stream4 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream4))
                        ->setAssessment($assessment2),
                ],
                "assessments" => [$assessment, $assessment2],
                "expected assessment streams" => [$stream2, $stream4]
            ],
        ];
    }


    /**
     * @dataProvider findActiveByAssessmentProvider
     */
    public function testFindActiveByAssessment(array $assessmentStreams, Assessment $assessment, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $assessmentsFromDb = $this->assessmentStreamRepository->findActiveByAssessment($assessment);
        self::assertSameSize($assessmentsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findActiveByAssessmentProvider(): array
    {
        return [
            "Positive 1: 3 assessmentStreams linked to 1 assessment, only 1 archived, expecting 2 assessment streams" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "expected assessment streams" => [
                    $stream1, $stream3
                ]
            ],
            "Positive 2: 3 assessmentStreams linked to 1 assessment, none is archived, expecting all 3" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::NEW)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream1))->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::IN_EVALUATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream2))->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage((new Evaluation())->setAssessmentStream($stream3))->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "expected assessment streams" => [
                    $stream1, $stream2, $stream3
                ]
            ]
        ];
    }

    /**
     * @dataProvider findUserAssignedStreamsByAssessmentsProvider
     */
    public function testFindUserAssignedStreamsByAssessments(array $assessmentStreams, Assessment $assessment, User $user, array $expectedAssessmentStreams)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $assessmentsFromDb = $this->assessmentStreamRepository->findUserAssignedStreamsByAssessments($assessment, $user);
        self::assertSameSize($assessmentsFromDb, $expectedAssessmentStreams);
        foreach ($assessmentsFromDb as $assessmentStream) {
            self::assertContains($assessmentStream, $expectedAssessmentStreams);
        }
    }

    public function findUserAssignedStreamsByAssessmentsProvider(): array
    {
        return [
            "Positive 1: 3 assessmentStreams linked to 1 assessment, only 1 archived, expecting 2 assessment streams" => [
                "all assessment streams" => [
                    ($stream1 = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                        ->addAssessmentStreamStage(($evaluation = new Evaluation())->setAssessmentStream($stream1)
                            ->addStageAssignment((new Assignment())->setStage($evaluation)->setUser($user = new User())))
                        ->setAssessment($assessment = new Assessment()),
                    ($stream2 = new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)
                        ->addAssessmentStreamStage(($evaluation = new Evaluation())->setAssessmentStream($stream2)
                            ->addStageAssignment((new Assignment())->setStage($evaluation)->setUser($user)))
                        ->setAssessment($assessment),
                    ($stream3 = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                        ->addAssessmentStreamStage(($evaluation = new Evaluation())->setAssessmentStream($stream3)
                            ->addStageAssignment((new Assignment())->setStage($evaluation)->setUser($user)))
                        ->setAssessment($assessment),
                ],
                "assessment" => $assessment,
                "user" => $user,
                "expected assessment streams" => [
                    $stream1, $stream3
                ]
            ],
        ];
    }
}
