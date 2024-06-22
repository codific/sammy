<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Improvement;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\ImprovementStatus;
use App\Enum\Role;
use App\Enum\ValidationStatus;
use App\Form\Application\ImprovementType;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentStreamRepository;
use App\Repository\ImprovementRepository;
use App\Repository\QuestionRepository;
use App\Service\AssessmentService;
use App\Service\MetamodelService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\ProjectBuilder;
use App\Tests\builders\UserBuilder;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImprovementControllerTest extends AbstractWebTestCase
{
    /**
     * @dataProvider saveImprovementPlanProvider
     */
    public function testSaveImprovementPlan(
        User $user,
        string $insertedPlan,
        string $expectedPlan,
        string $insertedDate,
        ?string $expectedDate,
        bool $expectedAnswer,
        AssessmentStatus $expectedStreamStatus,
        Project $project,
        AssessmentStatus $assessmentStreamStatus,
        ImprovementStatus $improvementStatus,
    ): void {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $container = self::getContainer();
        $assessment = $container->get(AssessmentService::class)->createAssessment($project);

        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(
            ["status" => \App\Enum\AssessmentStatus::NEW, "assessment" => $assessment]
        );

        $assessmentStream->setStatus($assessmentStreamStatus)
            ->addAssessmentStreamStage(
                (new Evaluation())
                    ->setAssessmentStream($assessmentStream)
                    ->setCompletedAt(new DateTime('now'))
                    ->setSubmittedBy($user)
                    ->setAssignedTo($user)
            )
            ->addAssessmentStreamStage(
                (new Validation())
                    ->setAssessmentStream($assessmentStream)
                    ->setStatus(\App\Enum\ValidationStatus::ACCEPTED)
            )
            ->addAssessmentStreamStage(
                (new Improvement())
                    ->setAssessmentStream($assessmentStream)
                    ->setStatus($improvementStatus)
            );
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $insertedAnswers = '{}';
        $expectedAnswerId = null;
        if ($expectedAnswer) {
            $streamActivity1 = $assessmentStream->getStream()->getStreamActivities()->current();
            $question1 = $container->get(QuestionRepository::class)->findOneBy(["activity" => $streamActivity1]);
            $answer1 = $question1->getAnswerSet()->getAnswerSetAnswers()->current();
            $insertedAnswers = json_encode([$question1->getId() => $answer1->getId()], JSON_THROW_ON_ERROR);
            $expectedAnswerId = $answer1->getId();
        }


        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_start_improve", ['id' => $assessmentStream->getId()]), [
            'improvement' => [
                'plan' => $insertedPlan,
                'targetDate' => $insertedDate,
                'newDesiredAnswers' => $insertedAnswers,
                'SAVE' => '',
            ],
        ]);

        $container = self::getContainer();
        $improvement = $container->get(ImprovementRepository::class)->find($assessmentStream->getCurrentStage()->getId());

        self::assertEquals($expectedPlan, $improvement->getPlan());

        $expectedDateTime = $expectedDate !== null ? new DateTime($expectedDate) : $expectedDate;
        self::assertEquals($expectedDateTime, $improvement->getTargetDate());
        self::assertEquals($expectedStreamStatus, $assessmentStream->getStatus());

        $savedAnswer = $container->get(AssessmentAnswerRepository::class)->findOneBy(
            ['answer' => $expectedAnswerId, 'type' => \App\Enum\AssessmentAnswerType::DESIRED]
        );
        self::assertEquals($expectedAnswerId, $savedAnswer?->getAnswer()?->getId());
    }

    private function saveImprovementPlanProvider(): array
    {
        $container = self::getContainer();

        $userInOrganization1 = (new User())
            ->setRoles(
                [
                    Role::USER->string(),
                    Role::EVALUATOR->string(),
                    Role::VALIDATOR->string(),
                    Role::MANAGER->string(),
                    Role::IMPROVER->string(),
                ]
            )
            ->setDateFormat('Y-m-d')
            ->setSecretKey("MFZWIZDEMRSQ====")
            ->setAgreedToTerms(true)
            ->setEmail(bin2hex(random_bytes(5))."@test.test")
            ->setPassword('$2a$12$yNaZmq0qLazJUMsiIbsO6eXW9v2uYilotB0uNFclWTywNOrpZLa9e'); //admin123

        $userInOrganization2 = (new User())
            ->setRoles([Role::USER->string(), Role::EVALUATOR->string(), Role::VALIDATOR->string()])
            ->setDateFormat('Y-m-d')
            ->setSecretKey("MFZWIZDEMRSQ====")
            ->setAgreedToTerms(true)
            ->setEmail(bin2hex(random_bytes(5))."@test.test")
            ->setPassword('$2a$12$yNaZmq0qLazJUMsiIbsO6eXW9v2uYilotB0uNFclWTywNOrpZLa9e'); //admin123


        return [
            "Positive 1 - User improver tries to save plan" => [
                $userInOrganization1, // user
                "test plan", // attempted saved plan
                "test plan", // expected plan
                date('Y-m-d'), // inserted date
                date('Y-m-d'), // expected date
                true, // expected answer
                \App\Enum\AssessmentStatus::VALIDATED, //expected status
                (new Project()),
                AssessmentStatus::VALIDATED,
                ImprovementStatus::NEW,
            ],
            "Negative 1 - User improver tries to save plan on incorrect stream state" => [
                $userInOrganization1, // user
                "test plan", // attempted saved plan]
                "", // expected plan
                date('Y-m-d'), // inserted date
                null, // expected date
                false, // expected answer
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, //expected status
                (new Project()),
                AssessmentStatus::IN_IMPROVEMENT,
                ImprovementStatus::IMPROVE,
            ],
        ];
    }

    /**
     * @dataProvider completeImproveStreamProvider
     */
    public function testCompleteImproveStream(User $user, Group $group, AssessmentStatus $assessmentStreamStatus, ImprovementStatus $improvementStatus)
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();


        $container = self::getContainer();
        $project1 = (new Project())->setName("test project");
        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project1)->setGroup($group));

        $assessment = $container->get(AssessmentService::class)->createAssessment($project1);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(
            ["status" => \App\Enum\AssessmentStatus::NEW, "assessment" => $assessment]
        );

        $assessmentStream
            ->setStatus($assessmentStreamStatus)
            ->addAssessmentStreamStage(
                (new Improvement())
                    ->setStatus($improvementStatus)
                    ->setAssessmentStream($assessmentStream)
                    ->setSubmittedBy($user)
            );
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects();

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_complete_improve", ['id' => $assessmentStream->getId()]));

        $assessmentStreamAfter = $this->entityManager->getRepository(AssessmentStream::class)->find($assessmentStream->getId());
        $improvementAfter = $this->entityManager->getRepository(Improvement::class)->find($assessmentStream->getLastImprovementStage()->getId());

        self::assertEquals(\App\Enum\AssessmentStatus::COMPLETE, $assessmentStreamAfter->getStatus());
        self::assertEquals(\App\Enum\ImprovementStatus::WONT_IMPROVE, $improvementAfter->getStatus());
    }

    private function completeImproveStreamProvider(): array
    {
        $userInOrganization = (new User())
            ->setRoles([Role::USER->string(), Role::IMPROVER->string()])
            ->setSecretKey("MFZWIZDEMRSQ====")
            ->setAgreedToTerms(true)
            ->setEmail(bin2hex(random_bytes(5))."@test.test")
            ->setPassword('$2a$12$yNaZmq0qLazJUMsiIbsO6eXW9v2uYilotB0uNFclWTywNOrpZLa9e'); //admin123

        $group = (new Group());

        return [
            "Positive test 1 - assessment stream is COMPLETE and status is set to WONT_IMPROVE" => [
                $userInOrganization, // user
                $group, // group
                AssessmentStatus::VALIDATED,
                ImprovementStatus::NEW,
            ],
        ];
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider testStartImproveEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testStartImproveEndpointsDOA(
        User $user,
        Project $project,
        Group $group,
        AssessmentStatus $assessmentStreamStatus,
        Stage $stage,
        array $payload,
        bool $expectedAnswer,
        int $expectedStatusCode
    ): void {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->persist($stage);
        $this->entityManager->flush();

        $container = self::getContainer();
        $assessmentStatusValidatedStageImprovement = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessmentStatusValidatedStageImprovement,
            ]);
        $assessmentStream->addAssessmentStreamStage(
            ($stage)
                ->setAssessmentStream($assessmentStream)
        )->setStatus($assessmentStreamStatus);
        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        $this->entityManager->flush();

        if ($expectedAnswer) {
            $streamActivity1 = $assessmentStream->getStream()->getStreamActivities()->current();
            /** @var Question $question1 */
            $question1 = $container->get(QuestionRepository::class)->findOneBy(["activity" => $streamActivity1]);
            $answer1 = $question1->getAnswerSet()->getAnswerSetAnswers()->current();
            $payload['newDesiredAnswers'] = json_encode([$question1->getId() => $answer1->getId()]);
        }


        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        if ($expectedStatusCode === Response::HTTP_NOT_FOUND) {
            $this->expectException(NotFoundHttpException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_start_improve", ['id' => $assessmentStream->getId()]), $payload);

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals(Response::HTTP_FOUND, $responseCode);
    }

    private function testStartImproveEndpointDOAProvider(): array
    {
        $userInOrgAndRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $userInOrgAndRoleUser = (new UserBuilder())->build();
        $userInOrg = (new UserBuilder())->withRoles([])->build();
        $userNotInOrgAndRoleUser = (new UserBuilder())->build();

        $group = (new Group());

        return [
            "Positive 1 - Test that starting an improvement at '/start-improve/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = validated, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::VALIDATED, // assessment stream status
                (new Improvement()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                        ImprovementType::SUBMIT_BUTTON => '',
                    ],
                ], // payload
                true,
                Response::HTTP_OK, // expected status code
            ],
            "Positive 2 - Test that starting an improvement at '/start-improve/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment stream status
                (new Improvement()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                        ImprovementType::SUBMIT_BUTTON => '',
                    ],
                ], // payload
                false,
                Response::HTTP_OK, // expected status code
            ],
            "Negative 1 - Test that starting an improvement at '/start-improve/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = validated, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::VALIDATED, // assessment stream status
                (new Evaluation()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                    ],
                ], // payload
                false,
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 2 - Test that starting an improvement at '/start-improve/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = validated, stage = evaluation" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::VALIDATED, // assessment stream status
                (new Evaluation()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                        ImprovementType::SUBMIT_BUTTON => '',
                    ],
                ], // payload
                false,
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 3 - Test that starting an improvement at '/start-improve/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::VALIDATED, // assessment stream status
                (new Evaluation()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                        ImprovementType::SUBMIT_BUTTON => '',
                    ],
                ], // payload
                false,
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 4 - Test that starting an improvement at '/start-improve/{id}' is not allowed for a user, who is in the org, assessment_stream_status = validated, stage = improvement" => [
                $userInOrg, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment stream status
                (new Improvement()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                        ImprovementType::SUBMIT_BUTTON => '',
                    ],
                ], // payload
                false,
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 5 - Test that starting an improvement at '/start-improve/{id}' is not allowed for a user, who is not in the org, has role_user, assessment_stream_status = validated, stage = improvement" => [
                $userNotInOrgAndRoleUser, // user
                (new Project()), // project
                $group, // group
                \App\Enum\AssessmentStatus::VALIDATED, // assessment stream status
                (new Evaluation()), // stage
                [
                    'improvement' => [
                        'targetDate' => '2045-01-02',
                        'plan' => "the plan is simple",
                        'newDesiredAnswers' => '{}',
                        ImprovementType::SUBMIT_BUTTON => '',
                    ],
                ], // payload
                false,
                Response::HTTP_FORBIDDEN, // expected status code
            ],
        ];
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider testCompleteImproveEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testCompleteImproveEndpointsDOA(
        User $user,
        Project $project,
        Group $group,
        Stage $stage,
        AssessmentStatus $assessmentStreamStatus,
        array $headers,
        int $expectedStatusCode
    ): void {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->persist($stage);
        $this->entityManager->flush();

        $container = self::getContainer();
        $assessmentStatusValidatedStageImprovement = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessmentStatusValidatedStageImprovement,
            ]);
        $assessmentStream->addAssessmentStreamStage(
            ($stage)
                ->setAssessmentStream($assessmentStream)
        )->setStatus($assessmentStreamStatus);
        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }
        if ($expectedStatusCode === Response::HTTP_NOT_FOUND) {
            $this->expectException(NotFoundHttpException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->followRedirects(true);
        $this->client->request("GET", $this->urlGenerator->generate("app_improvement_complete_improve", ['id' => $assessmentStream->getId()]), [], [], $headers);

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $responseCode);
    }

    private function testCompleteImproveEndpointDOAProvider(): array
    {
        $userInOrgAndRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $userInOrgAndRoleUser = (new UserBuilder())->build();
        $userInOrg = (new UserBuilder())->withRoles([])->build();
        $userNotInOrgAndRoleUser = (new UserBuilder())->build();

        $group = (new Group());


        return [
            "Positive 1 - Test that completing an improvement at '/complete-improve/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = validated, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()),
                $group,
                (new Improvement()),
                \App\Enum\AssessmentStatus::VALIDATED,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_OK, // expected status code
            ],
            "Positive 2 - Test that completing an improvement at '/complete-improve/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()),
                $group,
                (new Improvement()),
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_OK, // expected status code
            ],
            "Negative 1 - Test that completing an improvement at '/complete-improve/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = validated, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new Project()),
                $group,
                (new Improvement()),
                \App\Enum\AssessmentStatus::VALIDATED,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 2 - Test that completing an improvement at '/complete-improve/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = validated, stage = evaluation" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()),
                $group,
                (new Evaluation()),
                \App\Enum\AssessmentStatus::VALIDATED,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 3 - Test that completing an improvement at '/complete-improve/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new Project()),
                $group,
                (new Improvement()),
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 4 - Test that completing an improvement at '/complete-improve/{id}' is not allowed for a user, who is in the org, assessment_stream_status = validated, stage = improvement" => [
                $userInOrg, // user
                (new Project()),
                $group,
                (new Improvement()),
                \App\Enum\AssessmentStatus::VALIDATED,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 5 - Test that completing an improvement at '/complete-improve/{id}' is not allowed for a user, who is not in the org, has role_user, assessment_stream_status = validated, stage = improvement" => [
                $userNotInOrgAndRoleUser, // user
                (new Project()),
                $group,
                (new Improvement()),
                \App\Enum\AssessmentStatus::VALIDATED,
                [
                    'HTTP_REFERER' => 'https://127.0.0.1:8000/assessment/info/policy-and-compliance',
                ], // headers
                Response::HTTP_FORBIDDEN, // expected status code
            ],
        ];
    }

    /**
     * @group asvs
     * @group security
     * @dataProvider testReactivateImprovementEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testReactivateImprovementEndpointsDOA(
        User $user,
        Project $project,
        Group $group,
        Stage $stage,
        AssessmentStatus $assessmentStreamStatus,
        int $expectedResponseCode
    ) {
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->persist($stage);
        $this->entityManager->flush();

        $container = self::getContainer();
        $assessmentStatusValidatedStageImprovement = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessmentStatusValidatedStageImprovement,
            ]);
        $assessmentStream->addAssessmentStreamStage(
            ((new Evaluation()))
                ->setAssessmentStream($assessmentStream)
        )->setStatus($assessmentStreamStatus);
        $assessmentStream->addAssessmentStreamStage(
            ($stage)
                ->setAssessmentStream($assessmentStream)
        )->setStatus($assessmentStreamStatus);
        $this->entityManager->persist($assessmentStream);

        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        $this->entityManager->flush();

        if ($expectedResponseCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_finish_improvement", ['id' => $assessmentStream->getLastImprovementStage()->getId()]));

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedResponseCode, $responseCode);
    }

    private function testReactivateImprovementEndpointDOAProvider(): array
    {
        $userInOrgAndRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $userInOrgAndRoleUser = (new UserBuilder())->build();
        $userInOrg = (new UserBuilder())->withRoles([])->build();
        $userNotInOrgAndRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();

        $group = (new Group());

        return [
            "Positive 1 - Test that reactivating an improvement at '/reactivate-improvement/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()),
                $group,
                (new Improvement()),
                AssessmentStatus::IN_IMPROVEMENT,
                Response::HTTP_OK, // expected access
            ],
            "Positive 2 - Test that reactivating an improvement at '/reactivate-improvement/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = complete, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new Project()),
                $group,
                (new Improvement()),
                AssessmentStatus::COMPLETE,
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that reactivating an improvement at '/reactivate-improvement/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new Project()),
                $group,
                (new Improvement()),
                AssessmentStatus::IN_IMPROVEMENT,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that reactivating an improvement at '/reactivate-improvement/{id}' is not allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = complete, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new Project()),
                $group,
                (new Improvement()),
                AssessmentStatus::COMPLETE,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 3 - Test that reactivating an improvement at '/reactivate-improvement/{id}' is not allowed for a user, who is in the org, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrg, // user
                (new Project()),
                $group,
                (new Improvement()),
                AssessmentStatus::IN_IMPROVEMENT,
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }

    /**
     * @group asvs
     * @group security
     * @dataProvider testFinishImprovementEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testFinishImprovementEndpointsDOA(User $user, Project $project, AssessmentStatus $assessmentStreamStatus, int $expectedResponseCode)
    {
        $container = self::getContainer();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $group = (new Group());
        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        $assessment = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);
        $assessmentStream->addAssessmentStreamStage(
            (new Evaluation())
                ->setAssessmentStream($assessmentStream)
        )->addAssessmentStreamStage(
            (new Validation())
                ->setAssessmentStream($assessmentStream)
        )->addAssessmentStreamStage(
            (new Improvement())
                ->setAssessmentStream($assessmentStream)
        )->setStatus($assessmentStreamStatus);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();


        if ($expectedResponseCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_finish_improvement", ['id' => $assessmentStream->getLastStageByClass(Improvement::class)->getId()]));

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals(200, $responseCode);
    }

    private function testFinishImprovementEndpointDOAProvider(): array
    {
        $userInOrgAndRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $userInOrgAndRoleUser = (new UserBuilder())->build();
        $userInOrg = (new UserBuilder())->withRoles([])->build();
        $userNotInOrgAndRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();

        return [
            "Positive 1 - Test that finishing an improvement at '/finish-improvement/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new ProjectBuilder())->build(), // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment_stream_status
                Response::HTTP_OK, // expected response code
            ],
            "Positive 2 - Test that finishing an improvement at '/finish-improvement/{id}' is allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = complete, stage = improvement" => [
                $userInOrgAndRoleUserAndImprover, // user
                (new ProjectBuilder())->build(), // project
                \App\Enum\AssessmentStatus::COMPLETE, // assessment_stream_status
                Response::HTTP_OK, // expected response code
            ],
            "Negative 1 - Test that finishing an improvement at '/finish-improvement/{id}' is not allowed for a user, who is in the org, has role_user, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new ProjectBuilder())->build(), // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment_stream_status
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 2 - Test that finishing an improvement at '/finish-improvement/{id}' is not allowed for a user, who is in the org, has role_user, has role_improver, assessment_stream_status = complete, stage = improvement" => [
                $userInOrgAndRoleUser, // user
                (new ProjectBuilder())->build(), // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment_stream_status
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 3 - Test that finishing an improvement at '/finish-improvement/{id}' is not allowed for a user, who is in the org, assessment_stream_status = in_improvement, stage = improvement" => [
                $userInOrg, // user
                (new ProjectBuilder())->build(), // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment_stream_status
                Response::HTTP_FORBIDDEN, // expected response code
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider startImprovementForOtherGroupProvider
     * @testdox Group voter check - attempt to improve stream for other group $_dataName
     */
    public function testStartImprovementForOtherGroup(User $user, Project $project, AssessmentStatus $assessmentStreamStatus): void
    {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $assessment = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);
        $improvement = (new Improvement())->setAssessmentStream($assessmentStream);

        $assessmentStream->setStatus($assessmentStreamStatus);
        $assessmentStream->addAssessmentStreamStage($improvement);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();


        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            "POST",
            $this->urlGenerator->generate("app_improvement_start_improve", ['id' => $assessmentStream->getId()]),
            [
                "improvement" => [
                    "targetDate" => "2022-02-02",
                    "plan" => "fakePlan",
                    "newDesiredAnswers" => json_encode([]),
                    "SUBMIT" => "",
                ],
            ]
        );
    }

    private function startImprovementForOtherGroupProvider(): array
    {

        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();

        return [
            "Negative 1 - User2 from Group 2 tries to save improvement to Group 1, expect nothing to happen to the stream" => [
                $user2, // user
                (new ProjectBuilder())->build(), // project
                AssessmentStatus::VALIDATED, // status
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider completeImprovementForOtherGroupProvider
     * @testdox Group voter check - attempt to complete stream for other group $_dataName
     */
    public function testCompleteImprovementForOtherGroup(User $user, Project $project, AssessmentStatus $assessmentStreamStatus): void
    {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $assessment = $container->get(AssessmentService::class)->createAssessment($project);
        $project->setMetamodel($container->get(MetamodelService::class)->getSAMM());
        /** @var AssessmentStream $assessmentStream */
        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);

        $improvement = (new Improvement())->setAssessmentStream($assessmentStream);

        $assessmentStream->setStatus($assessmentStreamStatus);
        $assessmentStream->addAssessmentStreamStage($improvement);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();


        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_complete_improve", ['id' => $assessmentStream->getId()]));

    }

    private function completeImprovementForOtherGroupProvider(): array
    {

        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();

        return [
            "Negative 1 - User2 from Group 2 tries to complete stream for Group 1, expect nothing to happen to the stream" => [
                $user2, // user
                (new ProjectBuilder())->build(), // project
                AssessmentStatus::VALIDATED, // status
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider testFinishImprovementForOtherGroupProvider
     * @testdox Group voter check - attempt to finish stream for other group $_dataName
     */
    public function testFinishImprovementForOtherGroup(User $user, Project $project, AssessmentStatus $assessmentStreamStatus, ImprovementStatus $improvementStatus)
    {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $assessment = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);

        $evaluation = (new Evaluation())->setAssessmentStream($assessmentStream);
        $this->entityManager->persist($evaluation);

        $improvement = (new Improvement())->setAssessmentStream($assessmentStream)->setStatus($improvementStatus);
        $this->entityManager->persist($improvement);

        $assessmentStream->setStatus($assessmentStreamStatus);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();


        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_improvement_finish_improvement", ['id' => $improvement->getId()]));

    }

    private function testFinishImprovementForOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();

        return [
            "Negative 1 - User2 from Group 2 tries to finish stream for Group 1, expect nothing to happen to the stream" => [
                $user2,
                (new ProjectBuilder())->build(),
                AssessmentStatus::COMPLETE,
                ImprovementStatus::WONT_IMPROVE,
            ],
        ];
    }
}
