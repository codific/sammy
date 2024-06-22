<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\ValidationStatus;
use App\Repository\AssessmentStreamRepository;
use App\Repository\ImprovementRepository;
use App\Service\AssessmentAnswersService;
use App\Service\AssessmentStreamService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Voter\AssessmentStreamVoterHelper;

class AssessmentStreamServiceTest extends AbstractKernelTestCase
{
    private array $injectedBeans;

    public function setUp(): void
    {
        parent::setUp();
        $this->injectedBeans = [
            self::getContainer()->get(ImprovementRepository::class),
            self::getContainer()->get(AssessmentStreamRepository::class),
            self::getContainer()->get(AssessmentAnswersService::class),
            self::getContainer()->get(AssessmentStreamVoterHelper::class),
        ];
    }

    /**
     * @dataProvider getEvaluationStreamsProvider
     *
     * @testdox $_dataName
     */
    public function testGetEvaluationStreams(Assessment $assessment, User $user, array $expectedAssessmentStream)
    {
        $assessmentStreamServiceMock = $this->getMockBuilder(AssessmentStreamService::class)
            ->setConstructorArgs($this->injectedBeans)
            ->onlyMethods(['filterByAssignedTo'])
            ->getMock();
        $assessmentStreamServiceMock->method('filterByAssignedTo')->willReturn($expectedAssessmentStream);

        $result = $assessmentStreamServiceMock->getEvaluationStreams($assessment, $user);

        self::assertEquals($expectedAssessmentStream, $result);
    }

    public function getEvaluationStreamsProvider(): array
    {
        $user = new User();
        $evaluation = (new Evaluation());
        $assessment = (new Assessment());
        $assessmentStream = (new AssessmentStream())->addAssessmentStreamStage($evaluation)->setStatus(AssessmentStatus::IN_EVALUATION);

        return [
            'Test that the right assessmentStream is returned by getEvaluationStreams' => [
                $assessment,
                $user,
                [
                    [
                        'weight' => 2,
                        'assessmentStream' => $assessmentStream,
                        'securityPracticeScore' => 2,
                        'businessFunctionScore' => 2,
                        'naturalOrder' => 2,
                        'stage' => $evaluation,
                        'assignment' => null,
                    ],
                ], // expected assessment streams
            ],
        ];
    }

    /**
     * @dataProvider getNonVerifiedAnswersProvider
     *
     * @testdox $_dataName
     */
    public function testGetNonVerifiedAnswers(Assessment $assessment, User $user, array $expectedAssessmentStream)
    {
        $assessmentStreamServiceMock = $this->getMockBuilder(AssessmentStreamService::class)
            ->setConstructorArgs($this->injectedBeans)
            ->onlyMethods(['filterByAssignedTo'])
            ->getMock();
        $assessmentStreamServiceMock->method('filterByAssignedTo')->willReturn($expectedAssessmentStream);

        $result = $assessmentStreamServiceMock->getNonVerifiedAnswers($assessment, $user);

        self::assertEquals($expectedAssessmentStream, $result);
    }

    private function getNonVerifiedAnswersProvider(): array
    {
        $user = new User();
        $validation = (new Validation());
        $assessment = (new Assessment());
        $assessmentStream = (new AssessmentStream())->addAssessmentStreamStage($validation)->setStatus(AssessmentStatus::IN_VALIDATION);

        return [
            'Test that the right assessmentStream is returned by getEvaluationStreams' => [
                $assessment,
                $user,
                [
                    [
                        'weight' => 2,
                        'assessmentStream' => $assessmentStream,
                        'securityPracticeScore' => 2,
                        'businessFunctionScore' => 2,
                        'naturalOrder' => 2,
                        'stage' => $validation,
                        'assignment' => null,
                    ],
                ], // expected assessment streams
            ],
        ];
    }

    /**
     * @dataProvider getStreamsInOrForImprovementProvider
     *
     * @testdox $_dataName
     */
    public function testGetStreamsInOrForImprovement(Assessment $assessment, User $user, array $expectedAssessmentStream)
    {
        $assessmentStreamServiceMock = $this->getMockBuilder(AssessmentStreamService::class)
            ->setConstructorArgs($this->injectedBeans)
            ->onlyMethods(['filterByAssignedTo'])
            ->getMock();
        $assessmentStreamServiceMock->method('filterByAssignedTo')->willReturn($expectedAssessmentStream);

        $result = $assessmentStreamServiceMock->getStreamsInOrForImprovement($assessment, $user);

        self::assertEquals($expectedAssessmentStream, $result);
    }

    private function getStreamsInOrForImprovementProvider(): array
    {
        $user = new User();
        $improvement = (new Improvement());
        $assessment = (new Assessment());
        $assessmentStream = (new AssessmentStream())->addAssessmentStreamStage($improvement)->setStatus(AssessmentStatus::IN_IMPROVEMENT);

        return [
            'Test that the right assessmentStream is returned by getEvaluationStreams' => [
                $assessment,
                $user,
                [
                    [
                        'weight' => 2,
                        'assessmentStream' => $assessmentStream,
                        'securityPracticeScore' => 2,
                        'businessFunctionScore' => 2,
                        'naturalOrder' => 2,
                        'stage' => $improvement,
                        'assignment' => null,
                    ],
                ], // expected assessment streams
            ],
        ];
    }

    /**
     * @dataProvider getCompletedStreamsProvider
     *
     * @testdox $_dataName
     */
    public function testGetCompletedStreams(Assessment $assessment, array $expectedAssessmentStreams)
    {
        $this->entityManager->persist($assessment);
        foreach ($expectedAssessmentStreams as $expectedAssessmentStream) {
            $this->entityManager->persist($expectedAssessmentStream);
        }
        $this->entityManager->flush();
        $this->entityManager->refresh($assessment);
        $assessmentStreamServiceMock = self::getContainer()->get(AssessmentStreamService::class);

        $result = $assessmentStreamServiceMock->getCompletedStreams($assessment);

        self::assertEquals($expectedAssessmentStreams, $result);
    }

    private function getCompletedStreamsProvider(): array
    {
        $assessment = (new Assessment());
        $improvement = new Improvement();
        $assessmentStream = (new AssessmentStream())->setAssessment($assessment)->addAssessmentStreamStage($improvement)->setStatus(AssessmentStatus::COMPLETE);

        return [
            'Test that the right assessmentStream is returned by getEvaluationStreams' => [
                $assessment,
                [$assessmentStream], // expected assessment streams
            ],
        ];
    }

    /**
     * @dataProvider testGetPreviousAssessmentStreamProvider
     *
     * @testdox $_dataName
     */
    public function testGetPreviousAssessmentStream(AssessmentStream $assessmentStream, AssessmentStream $expectedAssessmentStream)
    {
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($expectedAssessmentStream);
        $this->entityManager->flush();
        $assessmentStreamService = self::getContainer()->get(AssessmentStreamService::class);

        $result = $assessmentStreamService->getPreviousAssessmentStream($assessmentStream);

        self::assertEquals($expectedAssessmentStream->getId(), $result->getId());
    }

    private function testGetPreviousAssessmentStreamProvider(): array
    {
        $assessmentStream = (new AssessmentStream());
        $newAssessmentStream = (new AssessmentStream())->setStatus(AssessmentStatus::NEW);
        $improvement = (new Improvement())->setNew($newAssessmentStream)->setAssessmentStream($assessmentStream);
        $newAssessmentStream->addAssessmentStreamStage($improvement);

        return [
            'Test that the previous assessment stream is returned by getPreviousAssessmentStream' => [
                $newAssessmentStream,
                $assessmentStream,
            ],
        ];
    }

    /**
     * @dataProvider testSetScoreProvider
     *
     * @testdox $_dataName
     */
    public function testSetScore(AssessmentStream $assessmentStream, bool $expectedNewScoreSet)
    {
        $assessmentStreamService = self::getContainer()->get(AssessmentStreamService::class);

        $result = $assessmentStreamService->setScore($assessmentStream);

        self::assertEquals($expectedNewScoreSet, $result);
    }

    private function testSetScoreProvider(): array
    {
        $assessmentStream = (new AssessmentStream())->setScore(5);
        $newAssessmentStream = (new AssessmentStream())->setStatus(AssessmentStatus::NEW);
        $improvement = (new Improvement())->setNew($newAssessmentStream)->setAssessmentStream($assessmentStream);
        $newAssessmentStream->addAssessmentStreamStage($improvement);

        return [
            'Test that the new score is set correctly and different from the old score by setScore' => [
                $newAssessmentStream,
                true, // expected new score set
            ],
        ];
    }



    /**
     * @dataProvider getByAssessmentAndIdsProvider
     *
     * @testdox $_dataName
     */
    public function testGetByAssessmentAndIds(Assessment $assessment, array $assessmentStreams, int $expectedCount)
    {
        $this->entityManager->persist($assessment);

        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->flush();

        $assessmentStreamService = self::getContainer()->get(AssessmentStreamService::class);

        $result = $assessmentStreamService->getByAssessmentAndIds($assessment, $assessmentStreams);

        $this->assertCount($expectedCount, $result);
    }

    private function getByAssessmentAndIdsProvider(): array
    {
        return [
            'Test that the right amount of assessment streams are returned by getByAssessmentAndIds' => [
                $assessment = (new Assessment()),
                [
                    (new AssessmentStream())->setAssessment($assessment),
                    (new AssessmentStream())->setAssessment($assessment),
                    (new AssessmentStream())->setAssessment($assessment),
                    (new AssessmentStream())->setAssessment($assessment),
                ],
                4, // expected count
            ],
        ];
    }

    /**
     * @dataProvider canStreamBeRetractedProvider
     *
     * @testdox $_dataName
     */
    public function testCanStreamBeRetracted(User $user, AssessmentStream $assessmentStream, array $toPersist, bool $expectedResult)
    {
        $this->entityManager->persist($user);
        foreach ($toPersist as $item) {
            $this->entityManager->persist($item);
        }
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        $assessmentStreamService = self::getContainer()->get(AssessmentStreamService::class);

        $result = $assessmentStreamService->canStreamBeRetracted($user, $assessmentStream);

        self::assertEquals($expectedResult, $result);
    }

    private function canStreamBeRetractedProvider(): array
    {
        $user = (new User());
        $evaluation = (new Evaluation())->setSubmittedBy($user);
        $improvement = new Improvement();
        $validation = new Validation();
        $assessmentStream = (new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)->addAssessmentStreamStage($evaluation);

        $assessmentStream1 = (new AssessmentStream())
            ->setStatus(AssessmentStatus::VALIDATED)
            ->addAssessmentStreamStage($evaluation)
            ->addAssessmentStreamStage($validation->setStatus(ValidationStatus::AUTO_ACCEPTED)->setComment('')->setSubmittedBy($user))
            ->addAssessmentStreamStage($improvement->setSubmittedBy($user));

        return [
            'Positive 1 - Test that the stream can be retracted while in validation by canStreamBeRetracted' => [
                $user,
                $assessmentStream,
                [$evaluation],
                true,
            ],
            'Positive 2 - Test that the stream can be retracted while not in validation by canStreamBeRetracted' => [
                $user,
                $assessmentStream1,
                [$validation, $improvement],
                true,
            ],
            'Negative 1 - Test that the stream cannot be retracted while not submitted by the user by canStreamBeRetracted' => [
                $user,
                new AssessmentStream(),
                [],
                false,
            ],
        ];
    }
}
