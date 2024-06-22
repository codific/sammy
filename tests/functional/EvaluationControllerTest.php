<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\Answer;
use App\Entity\AnswerSet;
use App\Entity\Assessment;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Improvement;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Remark;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\ImprovementStatus;
use App\Enum\Role;
use App\Enum\ValidationStatus;
use App\Repository\AssessmentStreamRepository;
use App\Repository\QuestionRepository;
use App\Service\AssessmentService;
use App\Service\MetamodelService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\UserBuilder;
use DateTime;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EvaluationControllerTest extends AbstractWebTestCase
{
    /**
     * @testdox Index access is successful when the user has a ROLE_USER role
     *
     * @throws \Exception
     */
    public function testIndexSuccessful()
    {
        // Arrange
        $stream = (new Stream());
        $this->entityManager->persist($stream);
        $this->entityManager->flush();
        $user = (new UserBuilder($this->entityManager))->build();

        $this->client->loginUser($user, "boardworks");

        // Act
        $this->client->request('GET', $this->urlGenerator->generate('app_evaluation_overview', ['id' => $stream->getId()]));

        // Assert
        self::assertResponseRedirects("/");
    }

    /**
     * @dataProvider rolesWithoutAccessProvider
     *
     * @testdox Index access denied when the user only has a $role role
     *
     * @throws \Exception
     */
    public function testIndexAccessDenied(string $role)
    {
        // Arrange
        $evaluation = (new Evaluation());

        $user = (new UserBuilder($this->entityManager))->withRoles([$role])->build();

        $this->entityManager->persist($evaluation);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        // Assert
        $this->expectException(AccessDeniedException::class);

        // Act
        $this->client->request('GET', $this->urlGenerator->generate('app_evaluation_overview', ['id' => $evaluation]));
    }

    public function rolesWithoutAccessProvider(): array
    {
        return array_reduce(
            array_filter(
                User::getAllRoles(),
                fn($value) => $value !== Role::USER->string(),
            ),
            function ($outArray, $role) {
                $outArray[$role] = [$role];

                return $outArray;
            },
            []
        );
    }

    /**
     * @group asvs
     * @group security
     *
     * @dataProvider saveChoiceEndpointDOAProvider
     *
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testSaveChoiceEndpointsDOA(array $entitiesToPersist, User $user, Project $project, AssessmentStatus $assessmentStatus, array $payload, int $expectedResponseCode): void
    {
        foreach ($entitiesToPersist as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ])->setStatus($assessmentStatus);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $payload['answerId'] = $payload['answerId']->getId();
        $payload['questionId'] = $payload['questionId']->getId();

        if ($expectedResponseCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request('POST', $this->urlGenerator->generate('app_evaluation_save_choice', ['id' => $assessmentStream->getId()]), $payload);

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedResponseCode, $responseCode);
    }

    private function saveChoiceEndpointDOAProvider(): array
    {
        $userRoleUserAndEvaluator = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string(), Role::MANAGER->string()])->build();
        $userRoleUser = (new UserBuilder())->build();
        $user = (new UserBuilder())->build();

        $group = (new Group());
        $group->addGroupGroupUser((new GroupUser())->setUser($userRoleUserAndEvaluator)->setGroup($group));
        $userRoleUserAndEvaluator->addUserGroupUser((new GroupUser())->setUser($userRoleUserAndEvaluator)->setGroup($group));
        $project1 = (new Project());
        $project2 = (new Project());
        $project3 = (new Project());
        $group->addGroupGroupProject((new GroupProject())->setProject($project1)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project2)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project3)->setGroup($group));

        $answer = (new Answer())->setText('yes');
        $answerSet = (new AnswerSet())->addAnswerSetAnswer($answer);
        $answer->setAnswerSet($answerSet);
        $question = (new Question())->setText('are you yes')->setAnswerSet($answerSet);
        $assessmentAnswer = (new AssessmentAnswer())->setQuestion($question)->setAnswer($answer);


        return [
            // ------ANSWER_STREAM-------
            "Positive 1 - Test that save to '/save-choice/{id}' is allowed for a user, who has role_user, is evaluator, assessment status = new and current stage = null" => [
                [$assessmentAnswer, $group], // entities to persist
                $userRoleUserAndEvaluator, // user
                $project1, // project
                \App\Enum\AssessmentStatus::NEW, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_OK, // expected response code
            ],
            "Positive 2 - Test that save to '/save-choice/{id}' is allowed for a user, who has role_user, is evaluator, assessment status = in_evaluation and current stage = evaluation" => [
                [$assessmentAnswer], // entities to persist
                $userRoleUserAndEvaluator, // user
                $project2, // project
                \App\Enum\AssessmentStatus::IN_EVALUATION, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_OK, // expected response code
            ],
            "Negative 1 - Test that save to '/save-choice/{id}' is not allowed for a user, who has role_user, is not evaluator, assessment status = new and current stage = null" => [
                [$assessmentAnswer], // entities to persist
                $userRoleUser, // user
                $project1, // project
                \App\Enum\AssessmentStatus::NEW, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 2 - Test that save to '/save-choice/{id}' is not allowed for a user, who has role_user, is not evaluator, assessment status = in_evaluation and current stage = evaluation" => [
                [$assessmentAnswer], // entities to persist
                $userRoleUser, // user
                $project2, // project
                \App\Enum\AssessmentStatus::IN_EVALUATION, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 3 - Test that save to '/save-choice/{id}' is not allowed for a user, who has role_user, is evaluator, assessment status = in_improvement and current stage = improvement" => [
                [$assessmentAnswer], // entities to persist
                $userRoleUserAndEvaluator, // user
                $project3, // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 4 - Test that save to '/save-choice/{id}' is not allowed for a user, who has role_user, is not evaluator, assessment status = in_improvement and current stage = improvement" => [
                [$assessmentAnswer], // entities to persist
                $userRoleUser, // user
                $project3, // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 5 - Test that save to '/save-choice/{id}' is not allowed for a user, who does not have role_user, is not evaluator, assessment status = new and current stage = null" => [
                [$assessmentAnswer], // entities to persist
                $user, // user
                $project1, // project
                \App\Enum\AssessmentStatus::NEW, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 6 - Test that save to '/save-choice/{id}' is not allowed for a user, who does not have role_user, is not evaluator, assessment status = in_evaluation and current stage = evaluation" => [
                [$assessmentAnswer], // entities to persist
                $user, // user
                $project2, // project
                \App\Enum\AssessmentStatus::IN_EVALUATION, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
            "Negative 7 - Test that save to '/save-choice/{id}' is not allowed for a user, who does not have role_user, is not evaluator, assessment status = in_improvement and current stage = improvement" => [
                [$assessmentAnswer], // entities to persist
                $user, // user
                $project3, // project
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT, // assessment status
                [
                    'answerId' => $assessmentAnswer->getAnswer(),
                    'questionId' => $assessmentAnswer->getQuestion(),
                    'type' => \App\Enum\AssessmentAnswerType::CURRENT->value,
                ], // payload
                Response::HTTP_FORBIDDEN, // expected response code
            ],
        ];
    }

    /**
     * @dataProvider testRetractSubmissionProvider
     */
    public function testRetractSubmission(
        array $entitiesToPersist,
        User $user,
        AssessmentStatus $assessmentStreamStatus,
        ValidationStatus $validationStatus,
        ?ImprovementStatus $improvementStatus,
        bool $hasAssessmentAnswer,
    ) {
        foreach ($entitiesToPersist as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $project = $entitiesToPersist[1];
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $assessmentStreams = self::getContainer()->get(AssessmentStreamRepository::class)->findBy(
            ["status" => \App\Enum\AssessmentStatus::NEW, "assessment" => $assessment]
        );

        $assessmentStream = $assessmentStreams[0];
        $evaluation = (new Evaluation())->setAssessmentStream($assessmentStream)->setCompletedAt(new DateTime('now'))->setSubmittedBy($user)->setAssignedTo($user);
        $validation = (new Validation())->setAssessmentStream($assessmentStream)->setStatus($validationStatus)->setCompletedAt(new DateTime('now'));
        $improvement = (new Improvement())->setAssessmentStream($assessmentStream);
        if ($improvementStatus !== null) {
            $improvement->setStatus($improvementStatus);
        }
        $assessmentStream
            ->setStatus($assessmentStreamStatus)
            ->addAssessmentStreamStage($evaluation)
            ->addAssessmentStreamStage($validation)
            ->addAssessmentStreamStage($improvement);
        if ($hasAssessmentAnswer) {
            $assessmentAnswer = new AssessmentAnswer();
            $assessmentAnswer->setType(\App\Enum\AssessmentAnswerType::DESIRED);
            $assessmentStream->getLastImprovementStage()->addStageAssessmentAnswer($assessmentAnswer);
        }
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $expectedRedirect = "/evaluation/".$assessmentStream->getStream()->getId()."/";

        $this->client->loginUser($user, "boardworks");

        $this->client->request('GET', '/dashboard');

        $this->client->request('POST', $this->urlGenerator->generate('app_evaluation_retract_submission', ['assessmentStream' => $assessmentStream->getId()]), [
            'retract_submission' => '',
        ]);

        self::assertResponseRedirects($expectedRedirect);
    }

    private function testRetractSubmissionProvider(): array
    {
        $userInOrganizationWhoSubmitted = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $userInOrganizationNotSubmitted = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();

        $project = (new Project())->setName("test project");

        $improvementStage = new Improvement();
        $remark = new Remark();
        $remark->setStage($improvementStage);
        $improvementStage->addStageRemark($remark);

        return [
            "Positive 1 - User that submitted the evaluation tries to retract submission" => [
                [$remark, $project], // entities to persist
                $userInOrganizationWhoSubmitted, // user
                \App\Enum\AssessmentStatus::VALIDATED, // assessment status
                \App\Enum\ValidationStatus::AUTO_ACCEPTED, // validation status
                null, // improvement status
                false, // has assessment answer
            ],
            'Negative 1 - User that did not submit the evaluation tries to retract submission' => [
                [$remark, $project], // entities to persist
                $userInOrganizationNotSubmitted,
                \App\Enum\AssessmentStatus::VALIDATED, // assessment status
                \App\Enum\ValidationStatus::AUTO_ACCEPTED, // validation status
                null, // improvement status
                false, // has assessment answer
            ],
            'Negative 2 - User that submitted the evaluation tries to to retract stream in improvement' => [
                [$remark, $project], // entities to persist
                $userInOrganizationWhoSubmitted,
                \App\Enum\AssessmentStatus::IN_IMPROVEMENT,
                \App\Enum\ValidationStatus::AUTO_ACCEPTED,
                \App\Enum\ImprovementStatus::IMPROVE,
                false,
            ],
            'Negative 3 - User that submitted the evaluation tries to to retract completed stream' => [
                [$remark, $project], // entities to persist
                $userInOrganizationWhoSubmitted,
                \App\Enum\AssessmentStatus::COMPLETE,
                \App\Enum\ValidationStatus::AUTO_ACCEPTED,
                \App\Enum\ImprovementStatus::WONT_IMPROVE,
                false,
            ],
            'Negative 4 - User that submitted the evaluation tries to to retract stream with desired assessment answer' => [
                [$remark, $project], // entities to persist
                $userInOrganizationWhoSubmitted,
                \App\Enum\AssessmentStatus::COMPLETE,
                \App\Enum\ValidationStatus::AUTO_ACCEPTED,
                \App\Enum\ImprovementStatus::WONT_IMPROVE,
                true,
            ],
            'Negative 5 - User that submitted the evaluation tries to to retract stream with remark in improvement' => [
                [$remark, $project], // entities to persist
                $userInOrganizationWhoSubmitted,
                \App\Enum\AssessmentStatus::VALIDATED,
                \App\Enum\ValidationStatus::AUTO_ACCEPTED,
                null,
                true,
            ],
            'Negative 6 - User that submitted the evaluation tries to to retract stream with comment in validation' => [
                [$remark, $project], // entities to persist
                $userInOrganizationWhoSubmitted,
                \App\Enum\AssessmentStatus::VALIDATED,
                \App\Enum\ValidationStatus::AUTO_ACCEPTED,
                null,
                true,
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     *
     * @dataProvider testSubmitStreamInOtherGroupProvider
     *
     * @testdox Group voter check - attempt to submit stream to other group $_dataName
     */
    public function testSubmitStreamInOtherGroup(User $user, Project $project, AssessmentStatus $assessmentStreamStatus, int $expectedStatusCode): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->expectException(AccessDeniedException::class);

        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        /** @var AssessmentStream $assessmentStream */
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $assessmentStream->setStatus($assessmentStreamStatus);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();


        $this->client->loginUser($user, "boardworks");

        $this->client->request('POST', $this->urlGenerator->generate('app_evaluation_submit', ['assessmentStream' => $assessmentStream->getId()]));
    }

    private function testSubmitStreamInOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $project = (new Project());

        return [
            'Negative 1 - User2 from Group 2 tries to submit stream to Group 1, expect access denied' => [
                $user2, // user
                $project, // assessment stream,
                AssessmentStatus::IN_EVALUATION, // assessment stream status
                Response::HTTP_FORBIDDEN, //expected code
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     *
     * @dataProvider testSaveChoiceInOtherGroupProvider
     *
     * @testdox Group voter check - attempt to submit stream to other group $_dataName
     */
    public function testSaveChoiceInOtherGroup(User $user, Project $project, AssessmentStatus $assessmentStreamStatus)
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->expectException(AccessDeniedException::class);

        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        /** @var AssessmentStream $assessmentStream */
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);

        $assessmentStream->setStatus($assessmentStreamStatus);
        /** @var Question $question * */
        $question = self::getContainer()->get(QuestionRepository::class)->findByMetamodel($assessment->getProject()->getMetamodel())[0];
        $answer = $question->getAnswers()[0];
        $this->entityManager->persist($answer);
        $this->entityManager->persist($question);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            'POST',
            $this->urlGenerator->generate('app_evaluation_save_choice', ['id' => $assessmentStream->getId()]),
            [
                'answerId' => $answer->getId(),
                'questionId' => $question->getId(),
            ]
        );
    }

    private function testSaveChoiceInOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $project = (new Project());

        return [
            'Negative 1 - User2 from Group 2 tries to save choice to Group 1, expect access denied' => [
                $user2, // user
                $project, // project
                AssessmentStatus::IN_EVALUATION, // assessment stream status
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     *
     * @dataProvider testSaveCheckboxChoiceInOtherGroupProvider
     *
     * @testdox Group voter check - attempt to submit stream to other group $_dataName
     */
    public function testSaveCheckboxChoiceInOtherGroup(User $user, Project $project, AssessmentStatus $assessmentStreamStatus)
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->expectException(AccessDeniedException::class);

        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        /** @var AssessmentStream $assessmentStream */
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);

        $assessmentStream->setStatus($assessmentStreamStatus);
        /** @var Question $question * */
        $question = self::getContainer()->get(QuestionRepository::class)->findByMetamodel($assessment->getProject()->getMetamodel())[0];
        $answer = $question->getAnswers()[0];
        $this->entityManager->persist($answer);
        $this->entityManager->persist($question);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        $payload = [
            [
                'key' => 1,
                'isChecked' => true,
                'questionId' => $question->getId(),
            ],
        ];

        $this->client->request(
            'POST',
            $this->urlGenerator->generate('app_evaluation_save_checkbox_choice', ['id' => $assessmentStream->getId()]),
            [
                'checkboxesData' => json_encode($payload),
            ]
        );
    }

    private function testSaveCheckboxChoiceInOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $project = (new Project());

        return [
            'Negative 1 - User2 from Group 2 tries to save checkbox choice to Group 1, expect access denied' => [
                $user2, // user
                $project, // project
                AssessmentStatus::IN_EVALUATION, // assessment stream status
            ],
        ];
    }
}
