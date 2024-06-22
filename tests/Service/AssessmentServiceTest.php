<?php

declare(strict_types=1);

namespace App\Tests\Service;

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
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\Role;
use App\Enum\ValidationStatus;
use App\Exception\SavePlanOnIncorrectStreamException;
use App\Exception\SaveRemarkOnIncorrectStreamException;
use App\Exception\SubmitStreamException;
use App\Repository\QuestionRepository;
use App\Service\AssessmentAnswersService;
use App\Service\AssessmentService;
use App\Service\MetamodelService;
use App\Tests\EntityManagerTestCase;
use App\Tests\_support\AbstractKernelTestCase;
use DateTime;

class AssessmentServiceTest extends AbstractKernelTestCase
{
    private AssessmentService $assessmentService;

    public function setUp(): void
    {
        $this->assessmentService = static::getContainer()->get(AssessmentService::class);
        parent::setUp();
    }

    // TODO TEST add metamodel to project
    public function testCreateAssessment()
    {
        $project = new Project();
        $assessment = $this->assessmentService->createAssessment($project);
        self::assertEquals($assessment->getProject(), $project);
        self::assertEquals($project->getAssessment(), $assessment);
        $assessment = $this->entityManager->getRepository(Assessment::class)->findOneBy(['id' => $assessment->getId()]);
        $assessmentStreams = $this->entityManager->getRepository(AssessmentStream::class)->findBy(['assessment' => $assessment->getId()]);
        $streams = self::getContainer()->get(MetamodelService::class)->getStreams();
        self::assertCount(count($streams), $assessmentStreams);
    }

    /**
     * @dataProvider testGetActiveStreamsProvider
     *
     * @testdox $_dataName
     */
    public function testGetActiveStreams(Assessment $assessment, array $assessmentStreams, int $expectedCount)
    {
        foreach ($assessmentStreams as $assessmentStream) {
            $assessment->addAssessmentAssessmentStream($assessmentStream);
        }

        $resultAssessmentStreams = $this->assessmentService->getActiveStreams($assessment);

        self::assertCount($expectedCount, $resultAssessmentStreams);
        foreach ($resultAssessmentStreams as $assessmentStream) {
            self::assertNotEquals($assessmentStream->getStatus(), AssessmentStatus::ARCHIVED);
        }
    }

    private function testGetActiveStreamsProvider(): array
    {
        return [
            'Positive 1 - Test that only active assessment streams are returned by getActiveStreams' => [
                $assessment = new Assessment(), // assessment
                [
                    (new AssessmentStream())->setStatus(AssessmentStatus::NEW),
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                    (new AssessmentStream())->setStatus(AssessmentStatus::COMPLETE),
                    (new AssessmentStream())->setStatus(AssessmentStatus::IN_EVALUATION),
                    (new AssessmentStream())->setStatus(AssessmentStatus::IN_IMPROVEMENT),
                ], // added assessment streams
                4, // expected count
            ],
            'Positive 2 - Test that only active assessment streams are returned by getActiveStreams' => [
                new Assessment(), // assessment
                [
                    (new AssessmentStream())->setStatus(AssessmentStatus::NEW),
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                    (new AssessmentStream())->setStatus(AssessmentStatus::IN_EVALUATION),
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                ], // added assessment streams
                2, // expected count
            ],
            'Negative 1 - Test that no assessment streams are returned by getActiveStreams when they are all archived' => [
                new Assessment(), // assessment
                [
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                    (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED),
                ], // added assessment streams
                0, // expected count
            ],
        ];
    }

    public function testSortAssessmentStreams()
    {
        $stream1 = (new Stream())->setOrder(3)
            ->setPractice(
                (new Practice())->setOrder(1)->setBusinessFunction((new BusinessFunction())->setOrder(2))
            );
        $stream2 = (new Stream())->setOrder(2)
            ->setPractice(
                (new Practice())->setOrder(2)->setBusinessFunction((new BusinessFunction())->setOrder(1))
            );
        $stream3 = (new Stream())->setOrder(1)
            ->setPractice(
                (new Practice())->setOrder(3)->setBusinessFunction((new BusinessFunction())->setOrder(1))
            );
        $result = $this->assessmentService::sortAssessmentStreams(
            [
                ($assessment1 = new AssessmentStream())->setStream($stream1),
                ($assessment2 = new AssessmentStream())->setStream($stream2),
                ($assessment3 = new AssessmentStream())->setStream($stream3),
            ]
        );
        self::assertEquals($assessment1, $result[2]);
        self::assertEquals($assessment2, $result[0]);
        self::assertEquals($assessment3, $result[1]);
    }

    public function testGetProgress()
    {
        /** @var Question $sammQuestion1 */
        $sammQuestion1 = $this->getObjectOfClass(Question::class, 1);
        $sammQuestion2 = $this->getObjectOfClass(Question::class, 2);

        ($assessment = new Assessment())->setId(123);
        ($assessmentStream1 = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion1->getActivity()->getStream());
        $assessment->addAssessmentAssessmentStream($assessmentStream1);
        ($assessmentStream2 = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion2->getActivity()->getStream());
        $assessment->addAssessmentAssessmentStream($assessmentStream2);
        $evaluation1 = (new Evaluation())->setAssessmentStream($assessmentStream1);
        $assessmentStream1->addAssessmentStreamStage($evaluation1);
        $evaluation2 = (new Evaluation())->setAssessmentStream($assessmentStream2);
        $assessmentStream2->addAssessmentStreamStage($evaluation2);

        $answer1 = (new AssessmentAnswer())->setType(\App\Enum\AssessmentAnswerType::CURRENT)->setStage($evaluation1)
            ->setAnswer($sammQuestion1->getAnswerSet()->getAnswerSetAnswers()->first())->setQuestion($sammQuestion1);
        $evaluation1->addStageAssessmentAnswer($answer1);

        $answer2 = (new AssessmentAnswer())->setType(\App\Enum\AssessmentAnswerType::CURRENT)->setStage($evaluation2)
            ->setAnswer($sammQuestion2->getAnswerSet()->getAnswerSetAnswers()->first())->setQuestion($sammQuestion2);
        $evaluation2->addStageAssessmentAnswer($answer2);

        $this->entityManager->persist($answer1);
        $this->entityManager->persist($answer2);
        $this->entityManager->flush();

        $assessment = $this->entityManager->getRepository(Assessment::class)->findOneBy(['id' => $assessment->getId()]);

        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $questions = self::getContainer()->get(QuestionRepository::class)->findByMetamodel($metamodel);
        $expected = 100 * (2 / sizeof($questions));

        self::assertEquals($expected, $this->assessmentService->getProgress($assessment));
    }

    public function testSubmitStream()
    {
        /** @var Question $sammQuestion1 */
        $sammQuestion1 = $this->getObjectOfClass(Question::class, 1);

        ($assessment = new Assessment())->setId(123);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion1->getActivity()->getStream());
        $user = new User();
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        static::assertNull($this->entityManager->getRepository(Evaluation::class)->findOneBy(['assessmentStream' => $assessmentStream]));

        /** @var MetamodelService $metamodelService */
        $metamodelService = static::getContainer()->get(MetamodelService::class);
        /** @var AssessmentAnswersService $assessmentAnswerService */
        $assessmentAnswerService = static::getContainer()->get(AssessmentAnswersService::class);

        $questions = $metamodelService->getQuestionsByStream($assessmentStream->getStream());
        foreach ($questions as $question) {
            $exception = null;
            try {
                $this->assessmentService->submitStreamWithAutoValidateAttempt($assessmentStream, $user);
            } catch (SubmitStreamException $e) {
                $exception = $e;
            }
            //Throw exception when stream is not fully answered
            self::assertInstanceOf(SubmitStreamException::class, $exception);

            $answer = $question->getAnswerSet()->getAnswerSetAnswers()[0];
            $assessmentAnswerService->saveAnswer($assessmentStream, $question, $answer, $user);
            $this->entityManager->persist($answer);
            $this->entityManager->flush();

        }
        $this->assessmentService->submitStreamWithAutoValidateAttempt($assessmentStream, $user);
        // evaluation is created
        static::assertNotNull($evaluation = $this->entityManager->getRepository(Evaluation::class)->findOneBy(['assessmentStream' => $assessmentStream]));
        // validation is created
        static::assertNotNull($validation = $this->entityManager->getRepository(Validation::class)->findOneBy(['assessmentStream' => $assessmentStream]));
        // given that the threshold is 0 and answer scores are 0, by default $validation has to be autoaccepted
        self::assertEquals(\App\Enum\ValidationStatus::AUTO_ACCEPTED, $validation->getStatus());
        // assigned to user
        self::assertNotNull($this->entityManager->getRepository(Assignment::class)->findOneBy(['stage' => $evaluation, 'user' => $user]));
    }


    public function testRetractStreamSubmission()
    {
        /** @var Question $sammQuestion1 */
        $sammQuestion1 = $this->getObjectOfClass(Question::class, 1);
        ($assessment = new Assessment())->setId(123);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion1->getActivity()->getStream());
        $user = new User();
        ($evaluation = new Evaluation())->setAssessmentStream($assessmentStream)->setSubmittedBy($user)
            ->addStageAssignment((new Assignment())->setStage($evaluation)->setUser($user));
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $assessmentStream->setStatus(AssessmentStatus::IN_VALIDATION);
        $validation = (new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream);
        $assessmentStream->addAssessmentStreamStage($validation);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        self::assertEquals($assessmentStream->getLastEvaluationStage(), $evaluation);
        //RETRACT STREAM IN VALIDATION
        $this->assessmentService->retractStreamSubmission($assessmentStream, $user);
        $assessmentStream = $this->entityManager->getRepository(AssessmentStream::class)->findOneBy(['id' => $assessmentStream->getId()]);
        //make sure the validation is set to retracted
        self::assertEquals($assessmentStream->getLastValidationStage()->getStatus(), \App\Enum\ValidationStatus::RETRACTED);
        //make sure a new evaluation exists
        self::assertNotEquals($assessmentStream->getLastEvaluationStage(), $evaluation);
        $assignment = $this->entityManager->getRepository(Assignment::class)->findOneBy(['stage' => $assessmentStream->getLastEvaluationStage()]);
        //assert same assignee
        self::assertEquals($assignment->getUser(), $user);

        // SETUP STREAM FOR AUTOVALIDATION
        $sammQuestion2 = $this->getObjectOfClass(Question::class, 2);
        ($assessmentStream2 = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion2->getActivity()->getStream());
        ($evaluation2 = new Evaluation())->setAssessmentStream($assessmentStream2)->setSubmittedBy($user)
            ->addStageAssignment((new Assignment())->setStage($evaluation2)->setUser($user));
        $assessmentStream2->addAssessmentStreamStage($evaluation2);
        $assessmentStream2->setStatus(AssessmentStatus::VALIDATED);
        $validation2 = (new Validation())->setStatus(\App\Enum\ValidationStatus::AUTO_ACCEPTED)->setAssessmentStream($assessmentStream2);
        $assessmentStream2->addAssessmentStreamStage($validation2);
        ($improvement2 = new Improvement())->setAssessmentStream($assessmentStream2)->setId(123);
        $assessmentStream2->addAssessmentStreamStage($improvement2);
        $this->entityManager->persist($assessmentStream2);
        // RETRACT STREAM IN VALIDATION
        $this->assessmentService->retractStreamSubmission($assessmentStream2, $user);
        //make sure the validation is set to retracted
        self::assertEquals($assessmentStream2->getLastValidationStage()->getStatus(), \App\Enum\ValidationStatus::RETRACTED);
        //make sure a new evaluation exists
        self::assertNotEquals($assessmentStream2->getLastEvaluationStage(), $evaluation);
        //make sure assignment is to the same user
        $assignment = $this->entityManager->getRepository(Assignment::class)->findOneBy(['stage' => $assessmentStream->getLastEvaluationStage()]);
        //assert same assignee
        self::assertEquals($assignment->getUser(), $user);
        //make sure improvement is trashed
        self::assertNull($assessmentStream->getLastImprovementStage());
    }

    public function testValidateStream()
    {
        // ACCEPT FLOW
        /** @var Question $sammQuestion1 */
        $sammQuestion1 = $this->getObjectOfClass(Question::class, 1);
        ($assessment = new Assessment())->setId(123);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion1->getActivity()->getStream());
        $evaluator = new User();
        ($evaluation = new Evaluation())->setAssessmentStream($assessmentStream)->setSubmittedBy($evaluator)
            ->addStageAssignment((new Assignment())->setStage($evaluation)->setUser($evaluator));
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $assessmentStream->setStatus(AssessmentStatus::IN_VALIDATION);
        $validation = (new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream);
        $assessmentStream->addAssessmentStreamStage($validation);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        $this->assessmentService->validateStream($assessmentStream, $validator = new User());
        $assessmentStream = $this->entityManager->getRepository(AssessmentStream::class)->findOneBy(['id' => $assessmentStream->getId()]);
        //make sure the validation is set to accepted
        self::assertEquals($assessmentStream->getLastValidationStage()->getStatus(), \App\Enum\ValidationStatus::ACCEPTED);
        //by validator
        self::assertEquals($assessmentStream->getLastValidationStage()->getSubmittedBy(), $validator);
        //make sure assessment is validated
        self::assertEquals($assessmentStream->getStatus(), AssessmentStatus::VALIDATED);
        //make sure an improvement stage is created
        self::assertNotNull($assessmentStream->getLastImprovementStage());

        // REJECT FLOW
        /** @var Question $sammQuestion1 */
        $sammQuestion1 = $this->getObjectOfClass(Question::class, 1);
        ($assessment = new Assessment())->setId(123);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setStream($sammQuestion1->getActivity()->getStream());
        $evaluator = new User();
        ($evaluation = new Evaluation())->setAssessmentStream($assessmentStream)->setSubmittedBy($evaluator)
            ->addStageAssignment((new Assignment())->setStage($evaluation)->setUser($evaluator));
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $assessmentStream->setStatus(AssessmentStatus::IN_VALIDATION);
        $validation = (new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream);
        $assessmentStream->addAssessmentStreamStage($validation);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        $this->assessmentService->validateStream($assessmentStream, $validator = new User(), null, \App\Enum\ValidationStatus::REJECTED);
        $assessmentStream = $this->entityManager->getRepository(AssessmentStream::class)->findOneBy(['id' => $assessmentStream->getId()]);
        //make sure the validation is set to rejected
        self::assertEquals($assessmentStream->getLastValidationStage()->getStatus(), \App\Enum\ValidationStatus::REJECTED);
        //make sure an improvement stage is not created
        self::assertNull($assessmentStream->getLastImprovementStage());
        //make sure a new evaluation stage is created
        self::assertNotEquals($evaluation, $assessmentStream->getLastEvaluationStage());
    }

    /**
     * @dataProvider canEditValidationProvider
     */
    public function testCanEditValidation(User $user, AssessmentStream $assessmentStream, bool $result)
    {
        if ($result) {
            self::assertTrue($this->assessmentService->canEditValidation($user, $assessmentStream));
        } else {
            self::assertFalse($this->assessmentService->canEditValidation($user, $assessmentStream));
        }
    }

    public function canEditValidationProvider(): array
    {
        return [
            'positive 1 - user who is the validator and has a role validator' => [
                ($user = new User())->setRoles([Role::VALIDATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                    ->addAssessmentStreamStage((new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream)->setSubmittedBy($user)),
                true,
            ],
            'negative 1 - user who has a validator role but is not the validator' => [
                ($user = new User())->setRoles([Role::VALIDATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                    ->addAssessmentStreamStage((new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream)->setSubmittedBy(new User())),
                false,
            ],
            'negative 2 - user who has no validator role' => [
                ($user = new User())->setRoles([Role::MANAGER->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::VALIDATED)
                    ->addAssessmentStreamStage((new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream)->setSubmittedBy(new User())),
                false,
            ],
            'negative 3 - assessment stream is not in validation state' => [
                ($user = new User())->setRoles([Role::MANAGER->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::COMPLETE)
                    ->addAssessmentStreamStage((new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream)->setSubmittedBy(new User())),
                false,
            ],
        ];
    }

    public function testEditValidation()
    {
        ($user = new User())->setRoles([Role::VALIDATOR->string()]);
        ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::COMPLETE)
            ->addAssessmentStreamStage((new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream)->setSubmittedBy($user));
        $this->assessmentService->editValidation($assessmentStream, "new remark");
        self::assertEquals($assessmentStream->getLastValidationStage()->getComment(), "new remark");
    }

    /**
     * @dataProvider getUserAccessProvider
     */
    public function testGetUserAccess(User $user, AssessmentStream $assessmentStream, bool $result)
    {
        if ($result) {
            self::assertTrue($this->assessmentService->getUserAccess($assessmentStream, $user));
        } else {
            self::assertFalse($this->assessmentService->getUserAccess($assessmentStream, $user));
        }
    }

    public function getUserAccessProvider(): array
    {
        return [
            'positive 1 - new stream, user is evaluator' => [
                ($user = new User())->setRoles([Role::EVALUATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::NEW),
                true,
            ],
            'positive 2 - stream in evaluation, user is evaluator' => [
                ($user = new User())->setRoles([Role::EVALUATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_EVALUATION),
                true,
            ],
            'positive 3 - stream in improvement, user is improver' => [
                ($user = new User())->setRoles([Role::IMPROVER->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_IMPROVEMENT),
                true,
            ],
            'positive 4 - stream in validation, user is validator and evaluation is submitted by another user' => [
                ($user = new User())->setRoles([Role::VALIDATOR->string(), Role::EVALUATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                    ->addAssessmentStreamStage((new Evaluation())->setSubmittedBy(new User())),
                true,
            ],
            'positive 5 - stream in validation, user is validator and evaluation is submitted by himself but user is also manager' => [
                ($user = new User())->setRoles([Role::VALIDATOR->string(), Role::EVALUATOR->string(), Role::MANAGER->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                    ->addAssessmentStreamStage((new Evaluation())->setSubmittedBy($user)),
                true,
            ],
            'negative 1 - stream in evaluation, user is validator' => [
                ($user = new User())->setRoles([Role::VALIDATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_EVALUATION),
                false,
            ],
            'negative 2 - stream in improvement, user is evaluator' => [
                ($user = new User())->setRoles([Role::EVALUATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_IMPROVEMENT),
                false,
            ],
            'negative 3 - stream in validation, user is validator but submitted the stream himself' => [
                ($user = new User())->setRoles([Role::VALIDATOR->string(), Role::EVALUATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION)
                    ->addAssessmentStreamStage((new Evaluation())->setSubmittedBy($user)),
                false,
            ],
            'negative 4 - stream in validation, user is evaluator' => [
                ($user = new User())->setRoles([Role::EVALUATOR->string()]),
                ($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::IN_VALIDATION),
                false,
            ],
        ];
    }

    public function testStartImprovementStream()
    {
        $steam = $this->getObjectOfClass(Stream::class, 1);
        ($improvement = new Improvement())->setAssessmentStream((new AssessmentStream())->setAssessment((new Assessment())->setProject(new Project()))->setStream($steam)->addAssessmentStreamStage($improvement))
            ->addStageAssignment((new Assignment())->setStage($improvement)->setUser($user = new User()));
        $date = new \DateTime('+1 month');
        $plan = 'Big plan';
        $this->entityManager->persist($improvement);
        $this->entityManager->flush();
        $this->assessmentService->startImprovementStream($improvement, $date, $plan, [1 => 4], new User());
        self::assertEquals($improvement->getStatus(), \App\Enum\ImprovementStatus::IMPROVE);
        self::assertEquals($improvement->getPlan(), "Big plan");
        self::assertNotNull($this->entityManager->getRepository(Assignment::class)->findOneBy(['stage' => $improvement, 'user' => $user]));
        $desiredAnswer = $this->entityManager->getRepository(AssessmentAnswer::class)->findOneBy(['stage' => $improvement, 'type' => \App\Enum\AssessmentAnswerType::DESIRED]);
        self::assertEquals($desiredAnswer->getAnswer()->getId(), 4);
    }

    public function testCompleteImprovementStream()
    {
        ($improvement = new Improvement())->setAssessmentStream((new AssessmentStream())->addAssessmentStreamStage($improvement))
            ->addStageAssignment((new Assignment())->setStage($improvement)->setUser($user = new User()));
        $this->entityManager->persist($improvement);
        $this->entityManager->flush();
        $this->assessmentService->completeImprovementStream($improvement);
        self::assertEquals($improvement->getAssessmentStream()->getStatus(), AssessmentStatus::COMPLETE);
        self::assertEquals($improvement->getStatus(), \App\Enum\ImprovementStatus::WONT_IMPROVE);
        self::assertNotNull($this->entityManager->getRepository(Assignment::class)->findOneBy(['stage' => $improvement, 'user' => $user])->getDeletedAt());
    }

    public function testFinishImprovementStream()
    {
        ($improvement = new Improvement())->setAssessmentStream(($assessmentStream = new AssessmentStream())->addAssessmentStreamStage($improvement))
            ->addStageAssignment((new Assignment())->setStage($improvement)->setUser($user = new User()));
        ($evaluation = new Evaluation())->setAssessmentStream($assessmentStream);
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        $this->assessmentService->finishImprovementStream($improvement, $user);
        self::assertEquals($improvement->getAssessmentStream()->getStatus(), AssessmentStatus::ARCHIVED);
        self::assertNotNull($improvement->getNew());
        //make sure a new assessment stream is created
        $newAssessmentStream = $this->entityManager->getRepository(AssessmentStream::class)->findOneBy([], ['id' => 'DESC']);
        self::assertNotEquals($assessmentStream, $newAssessmentStream);
    }

    public function testReactivateImprovementStream()
    {
        ($improvement = new Improvement())->setAssessmentStream(($assessmentStream = new AssessmentStream())->addAssessmentStreamStage($improvement))
            ->addStageAssignment((new Assignment())->setStage($improvement)->setUser($user = new User()));
        $assessmentStream->setStatus(AssessmentStatus::COMPLETE);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        $this->assessmentService->reactivateImprovementStream($improvement);
        self::assertEquals($improvement->getAssessmentStream()->getStatus(), AssessmentStatus::VALIDATED);
        self::assertEquals($improvement->getStatus(), \App\Enum\ImprovementStatus::NEW);
    }

    public function testSaveImprovementPlan()
    {
        $steam = $this->getObjectOfClass(Stream::class, 1);
        ($improvement = new Improvement())->setAssessmentStream((new AssessmentStream())->setAssessment((new Assessment())->setProject(new Project()))->setStream($steam)->addAssessmentStreamStage($improvement))
            ->addStageAssignment((new Assignment())->setStage($improvement)->setUser($user = new User()));
        $date = new \DateTime('+1 month');
        $plan = 'Big plan';
        $this->entityManager->persist($improvement);
        $this->entityManager->flush();
        $this->assessmentService->saveImprovementPlan($improvement, $date, $plan, [1 => 4], new User());
        $desiredAnswer = $this->entityManager->getRepository(AssessmentAnswer::class)->findOneBy(['stage' => $improvement, 'type' => \App\Enum\AssessmentAnswerType::DESIRED]);
        self::assertEquals($desiredAnswer->getAnswer()->getId(), 4);

        $improvement->setStatus(\App\Enum\ImprovementStatus::WONT_IMPROVE);
        $this->expectExceptionObject(new SavePlanOnIncorrectStreamException());
        $this->assessmentService->saveImprovementPlan($improvement, $date, $plan, [1 => 4], new User());
    }

    public function testGetValidatedAssessmentStreamsByDate()
    {
        ($assessment = new Assessment())->setId(1);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setScore(2)->setStream($stream = new Stream());
        $assessmentStream->setStatus(AssessmentStatus::VALIDATED);
        $validation = (new Validation())->setStatus(\App\Enum\ValidationStatus::ACCEPTED)->setAssessmentStream($assessmentStream)->setCompletedAt(new \DateTime('2020-02-02'));
        $assessmentStream->addAssessmentStreamStage($validation);
        $assessment->addAssessmentAssessmentStream($assessmentStream);

        ($assessmentStream2 = new AssessmentStream())->setAssessment($assessment)->setScore(1)->setStream($stream);
        $assessmentStream2->setStatus(AssessmentStatus::VALIDATED);
        $validation2 = (new Validation())->setStatus(\App\Enum\ValidationStatus::ACCEPTED)->setAssessmentStream($assessmentStream2)->setCompletedAt(new \DateTime('2020-01-02'));
        $assessmentStream2->addAssessmentStreamStage($validation2);
        $assessment->addAssessmentAssessmentStream($assessmentStream2);

        ($assessmentStream3 = new AssessmentStream())->setAssessment($assessment)->setScore(3)->setStream(new Stream());
        $assessmentStream3->setStatus(AssessmentStatus::VALIDATED);
        $validation3 = (new Validation())->setStatus(\App\Enum\ValidationStatus::AUTO_ACCEPTED)->setAssessmentStream($assessmentStream3)->setCompletedAt(new \DateTime('2020-03-04'));
        $assessmentStream3->addAssessmentStreamStage($validation3);
        $assessment->addAssessmentAssessmentStream($assessmentStream3);

        ($assessmentStream4 = new AssessmentStream())->setAssessment($assessment)->setScore(4)->setStream(new Stream());
        $assessmentStream4->setStatus(AssessmentStatus::IN_VALIDATION);
        $validation4 = (new Validation())->setStatus(\App\Enum\ValidationStatus::ACCEPTED)->setAssessmentStream($assessmentStream4)->setCompletedAt(new \DateTime('2020-02-03'));
        $assessmentStream4->addAssessmentStreamStage($validation4);
        $assessment->addAssessmentAssessmentStream($assessmentStream4);

        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        self::assertEmpty($this->assessmentService->getValidatedAssessmentStreamsByDate(null, new DateTime('2020-02-02')));
        self::assertEmpty($this->assessmentService->getValidatedAssessmentStreamsByDate($assessment, new DateTime('2020-01-01')));
        self::assertCount(1, $this->assessmentService->getValidatedAssessmentStreamsByDate($assessment, new DateTime('2020-02-02')));
        self::assertCount(2, $this->assessmentService->getValidatedAssessmentStreamsByDate($assessment, new DateTime('2020-03-04')));
    }

    public function atestGetUnValidatedAssessmentStreamsByDate()
    {
        ($assessment = new Assessment())->setId(1);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setScore(2)->setStream($stream = new Stream());
        $assessmentStream->setStatus(AssessmentStatus::VALIDATED);
        ($evaluation = new Evaluation())->setAssessmentStream($assessmentStream)->setCompletedAt(new \DateTime('2020-04-04'));
        $validation = (new Validation())->setStatus(\App\Enum\ValidationStatus::ACCEPTED)->setAssessmentStream($assessmentStream)->setCompletedAt(new \DateTime('2020-02-02'));
        $assessmentStream->addAssessmentStreamStage($validation);
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $assessment->addAssessmentAssessmentStream($assessmentStream);

        ($assessmentStream3 = new AssessmentStream())->setAssessment($assessment)->setScore(3)->setStream(new Stream());
        $assessmentStream3->setStatus(AssessmentStatus::VALIDATED);
        ($evaluation3 = new Evaluation())->setAssessmentStream($assessmentStream3)->setCompletedAt(new \DateTime('2020-04-04'));
        $validation3 = (new Validation())->setStatus(\App\Enum\ValidationStatus::AUTO_ACCEPTED)->setAssessmentStream($assessmentStream3)->setCompletedAt(new \DateTime('2020-03-04'));
        $assessmentStream3->addAssessmentStreamStage($validation3);
        $assessmentStream3->addAssessmentStreamStage($evaluation3);
        $assessment->addAssessmentAssessmentStream($assessmentStream3);

        ($assessmentStream4 = new AssessmentStream())->setAssessment($assessment)->setScore(4)->setStream(new Stream());
        $assessmentStream4->setStatus(AssessmentStatus::IN_VALIDATION);
        ($evaluation4 = new Evaluation())->setAssessmentStream($assessmentStream4);
        $assessmentStream4->addAssessmentStreamStage($evaluation4);
        $assessment->addAssessmentAssessmentStream($assessmentStream4);

        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
        $evaluation->setCreatedAt(new \DateTime('2020-02-02'));
        $evaluation3->setCreatedAt(new \DateTime('2020-03-04'));
        $evaluation4->setCreatedAt(new \DateTime('2020-02-03'));
        $this->entityManager->persist($evaluation);
        $this->entityManager->persist($evaluation3);
        $this->entityManager->persist($evaluation4);
        $this->entityManager->flush();


        self::assertEmpty($this->assessmentService->getAssessmentStreamsByDate(null, new DateTime('2020-02-02')));
        self::assertEmpty($this->assessmentService->getAssessmentStreamsByDate($assessment, new DateTime('2020-01-01')));
        self::assertEmpty($this->assessmentService->getAssessmentStreamsByDate($assessment, new DateTime('2020-02-01')));
        self::assertEmpty($this->assessmentService->getAssessmentStreamsByDate($assessment, new DateTime('2020-02-02')));
        self::assertCount(1, $this->assessmentService->getAssessmentStreamsByDate($assessment, new DateTime('2020-03-04')));
    }

    public function testGetValidatedAssessmentStreams()
    {
        ($assessment = new Assessment())->setId(1);
        ($assessmentStream = new AssessmentStream())->setAssessment($assessment)->setScore(2)->setStream($stream = new Stream());
        $assessmentStream->setStatus(AssessmentStatus::VALIDATED);
        $validation = (new Validation())->setStatus(\App\Enum\ValidationStatus::ACCEPTED)->setAssessmentStream($assessmentStream)->setCompletedAt(new \DateTime('2020-01-02'));
        $assessmentStream->addAssessmentStreamStage($validation);
        $assessment->addAssessmentAssessmentStream($assessmentStream);
        ($project1 = new Project())->setAssessment($assessment);

        ($assessment2 = new Assessment())->setId(1);
        ($assessmentStream2 = new AssessmentStream())->setAssessment($assessment2)->setScore(2)->setStream($stream = new Stream());
        $assessmentStream2->setStatus(AssessmentStatus::VALIDATED);
        $validation2 = (new Validation())->setStatus(\App\Enum\ValidationStatus::ACCEPTED)->setAssessmentStream($assessmentStream2)->setCompletedAt(new \DateTime('2020-02-02'));
        $assessmentStream2->addAssessmentStreamStage($validation2);
        $assessment2->addAssessmentAssessmentStream($assessmentStream2);
        ($project2 = new Project())->setAssessment($assessment2);

        $this->entityManager->persist($project1);
        $this->entityManager->persist($project2);
        $this->entityManager->flush();

        self::assertEmpty($this->assessmentService->getValidatedAssessmentStreams(new DateTime('2019-01-01'), $project1, $project2));
        self::assertCount(1, $this->assessmentService->getValidatedAssessmentStreams(new DateTime('2020-01-02'), $project1, $project2));
        self::assertCount(2, $this->assessmentService->getValidatedAssessmentStreams(new DateTime('2020-02-03'), $project1, $project2));
    }

    public function testSaveValidationRemark()
    {
        ($user = new User())->setRoles([Role::VALIDATOR->string()]);
        $steam = $this->getObjectOfClass(Stream::class, 1);
        ($assessmentStream = new AssessmentStream())->setAssessment((new Assessment())->setProject(new Project()))->setStream($steam)->setStatus(AssessmentStatus::COMPLETE)
            ->addAssessmentStreamStage((new Validation())->setStatus(\App\Enum\ValidationStatus::NEW)->setAssessmentStream($assessmentStream)->setSubmittedBy($user));
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();
        $this->assessmentService->saveValidationRemark($assessmentStream, "new remark", $user);
        self::assertEquals($assessmentStream->getLastValidationStage()->getComment(), "new remark");

        $assessmentStream->getLastValidationStage()->setStatus(\App\Enum\ValidationStatus::REJECTED);
        $this->expectExceptionObject(new SaveRemarkOnIncorrectStreamException());
        $this->assessmentService->saveValidationRemark($assessmentStream, 'new remark', $user);
    }

    /**
     * @dataProvider testValidationRemarksDeletedOnRetractProvider
     *
     * @testdox $_dataName
     */
    public function testValidationRemarksDeletedOnRetract(AssessmentStream $assessmentStream, User $user, string|null $remark, ValidationStatus $status): void
    {
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $resultBeforeValidationRemark = $assessmentStream->getLastValidationStage()->getComment();
        $oldEvaluationSubmittedBy = $assessmentStream->getLastEvaluationStage()->getSubmittedBy();

        self::assertNotNull($resultBeforeValidationRemark);

        $this->assessmentService->validateStream($assessmentStream, $user, $remark, $status);

        $resultAfterValidationRemark = $assessmentStream->getLastValidationStage()->getComment();
        self::assertNull($resultAfterValidationRemark);

        $assignment = $this->entityManager->getRepository(Assignment::class)->findOneBy([
            'user' => $oldEvaluationSubmittedBy,
            'assignedBy' => $user,
        ]);
        self::assertNotNull($assignment);
    }

    private function testValidationRemarksDeletedOnRetractProvider(): array
    {
        return [
            'Test that retraction of the stream deletes the validation remark by validateStream' => [
                (new AssessmentStream())
                    ->addAssessmentStreamStage((new Evaluation())->setSubmittedBy(new User()))
                    ->addAssessmentStreamStage((new Validation())->setComment('this is a validation remark '.bin2hex(random_bytes(5)))), // assessment stream
                new User(), // user
                null, // remark
                ValidationStatus::RETRACTED, // status
            ],
        ];
    }
}
