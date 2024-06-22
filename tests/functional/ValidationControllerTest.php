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
use App\Entity\Remark;
use App\Entity\Stage;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\Role;
use App\Exception\SaveRemarkOnIncorrectStreamException;
use App\Repository\AssessmentStreamRepository;
use App\Repository\ValidationRepository;
use App\Service\AssessmentService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\GroupBuilder;
use App\Tests\builders\ProjectBuilder;
use App\Tests\builders\UserBuilder;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidationControllerTest extends AbstractWebTestCase
{
    /**
     * @group asvs
     * @group security
     * @dataProvider testDeleteRemarkEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testDeleteRemarkEndpointsDOA(User $user, ?User $remarkUser, Project $project, Group $group, AssessmentStatus $assessmentStreamStatus, bool $userInGroupProject, int $expectedStatusCode): void
    {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        if ($userInGroupProject) {
            $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        }
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));

        $makeRemarkByUserAndAssessmentStream = function (?User $user, AssessmentStream $assessmentStream) {
            return (new Remark())
                ->setUser($user)
                ->setStage((new Stage())->setAssessmentStream($assessmentStream));
        };

        $assessment = ($container->get(AssessmentService::class)->createAssessment($project));
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ])->setStatus($assessmentStreamStatus);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $remark = $makeRemarkByUserAndAssessmentStream($remarkUser, $assessmentStream);
        $this->entityManager->persist($remark);
        $this->entityManager->flush();


        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->entityManager->beginTransaction();
        $this->entityManager->flush();
        $this->client->request("DELETE", $this->urlGenerator->generate("app_validation_delete_remark", ['id' => $remark->getId()]));

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $responseCode);
    }

    private function testDeleteRemarkEndpointDOAProvider(): array
    {
        $userInOrganizationAndRoleUserAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        $userInOrganizationAndRoleUser = (new UserBuilder())->build();
        $userInOrganization = (new UserBuilder())->build();

        $group = (new GroupBuilder())->build();

        return [
            "Positive 1 - Test that deletion to '/delete-remark/{id}' is allowed for a user who is in the org, has role_user, has manager, is the remark owner, assessment_stream_status = in_evaluation" => [
                $userInOrganizationAndRoleUserAndManager, // user
                $userInOrganizationAndRoleUserAndManager, // remark_user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::IN_EVALUATION,
                false,
                Response::HTTP_OK,// expected access
            ],
            "Positive 2 - Test that deletion to '/delete-remark/{id}' is allowed for a user who is in the org, has role_user, does not have manager, is the remark owner, assessment_stream_status = in_evaluation" => [
                $userInOrganizationAndRoleUser, // user
                $userInOrganizationAndRoleUser, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::IN_EVALUATION,
                true,
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that deletion to '/delete-remark/{id}' is not allowed for a user who is in the org, has manager role, is the remark owner, assessment_stream_status = archived" => [
                $userInOrganizationAndRoleUserAndManager, // user
                $userInOrganizationAndRoleUserAndManager, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::ARCHIVED,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that deletion to '/delete-remark/{id}' is not allowed for a user who is in the org, does not have role_user, does not have manager, is the remark owner, assessment_stream_status = in_evaluation" => [
                $userInOrganization, // user
                $userInOrganization, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::IN_EVALUATION,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 3 - Test that deletion to '/delete-remark/{id}' is not allowed for a user who is in the org, has role_user, has manager, is not the remark owner, assessment_stream_status = archived" => [
                $userInOrganizationAndRoleUserAndManager, // user
                null, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::ARCHIVED,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 4 - Test that deletion to '/delete-remark/{id}' is not allowed for a user who is in the org, has role_user, does not have manager, is the remark owner, assessment_stream_status = archived" => [
                $userInOrganizationAndRoleUser, // user
                $userInOrganizationAndRoleUser, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::ARCHIVED,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 5 - Test that deletion to '/delete-remark/{id}' is not allowed for a user who is in the org, has role_user, does not have manager, is the remark owner, assessment_stream_status = archived" => [
                $userInOrganizationAndRoleUser, // user
                $userInOrganizationAndRoleUser, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::ARCHIVED,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 6 - Test that deletion to '/delete-remark/{id}' is not allowed for a user who is in the org, has role_user, has manager, is not the remark owner, assessment_stream_status = in_evaluation" => [
                $userInOrganizationAndRoleUserAndManager, // user
                null, // remark user
                (new ProjectBuilder())->build(),
                $group,
                \App\Enum\AssessmentStatus::IN_EVALUATION,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider testValidateEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testValidateEndpointsDOA(User $user, Project $project, AssessmentStatus $assessmentStreamStatus, array $payload, int $expectedStatusCode)
    {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $makeAssessmentByProject = function (Project $project) use ($container) {
            $assessment = $container->get(AssessmentService::class)->createAssessment($project);

            return $assessment;
        };

        $setAssessmentStreamProps = function (AssessmentStream $assessmentStream, array $stages, AssessmentStatus $status) {
            /** @var Stage $stage */
            foreach ($stages as $stage) {
                $stage->setAssessmentStream($assessmentStream);
                $assessmentStream->addAssessmentStreamStage($stage);
            };
            $assessmentStream
                ->setStatus($status);

            return $assessmentStream;
        };

        $assessment = $makeAssessmentByProject($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $assessmentStream = $setAssessmentStreamProps(
            $assessmentStream,
            [
                (new Evaluation())->setSubmittedBy(new User()),
                (new Validation()),
            ],
            $assessmentStreamStatus,
        );
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");
        $this->client->followRedirects(true);
        $this->client->request("POST", $this->urlGenerator->generate("app_validation_validate", ['id' => $assessmentStream->getId()]), $payload);

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $responseCode);
    }

    private function testValidateEndpointDOAProvider(): array
    {

        $userInOrgAndRoleUserAndValidatorAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string(), Role::MANAGER->string()])->build();
        $userInOrgAndRoleUserAndValidator = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string()])->build();
        $userInOrgAndRoleUserAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrgAndRoleUser = (new UserBuilder())->build();
        $userInOrg = (new UserBuilder())->withRoles([])->build();
        $userNotInOrgAndRoleUserAndValidatorAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string(), Role::MANAGER->string()])->build();
        $userNotInOrgAndRoleUserAndValidator = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string()])->build();
        $userNotInOrgAndRoleUserAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userNotInOrgAndRoleUser = (new UserBuilder())->build();
        $userNotInOrg = (new UserBuilder())->withRoles([])->build();


        return [
            "Positive 1 - Test that validation to '/validate/{id}' is allowed for a user, who is in the org, has role_user, role_validator, role_manager, assessment stream status = in_validation and not submitted by him" => [
                $userInOrgAndRoleUserAndValidatorAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_OK, // expected access
            ],
            "Positive 3 - Test that validation to '/validate/{id}' is allowed for a user, who is in the org, has role_user, role_validator, role_manager, assessment stream status = in_validation and is submitted by him" => [
                $userInOrgAndRoleUserAndValidatorAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, role_validator, assessment stream status = in_validation and is submitted by him" => [
                $userInOrgAndRoleUserAndValidator, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_manager, assessment stream status = in_validation and is not submitted by him" => [
                $userInOrgAndRoleUserAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 3 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, assessment stream status = in_validation and is not submitted by him" => [
                $userInOrgAndRoleUser, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 4 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_manager, assessment stream status = in_validation and is submitted by him" => [
                $userInOrgAndRoleUserAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 5 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, assessment stream status = in_validation and is submitted by him" => [
                $userInOrgAndRoleUser, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 6 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_validator, has role_manager, assessment stream status != in_validation and is not submitted by him" => [
                $userInOrgAndRoleUserAndValidatorAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 7 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_validator, assessment stream status != in_validation and is not submitted by him" => [
                $userInOrgAndRoleUserAndValidator, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 8 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_validator,has role_manager, assessment stream status != in_validation and is submitted by him" => [
                $userInOrgAndRoleUserAndValidatorAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 9 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_validator, assessment stream status != in_validation and is submitted by him" => [
                $userInOrgAndRoleUserAndValidator, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 10 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_manager, assessment stream status != in_validation and is not submitted by him" => [
                $userInOrgAndRoleUserAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 11 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, assessment stream status != in_validation and is not submitted by him" => [
                $userInOrgAndRoleUser, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 12 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, has role_manager, assessment stream status != in_validation and is submitted by him" => [
                $userInOrgAndRoleUserAndManager, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 13 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, has role_user, assessment stream status != in_validation and is submitted by him" => [
                $userInOrgAndRoleUser, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 14 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, assessment stream status = in_validation and is not submitted by him" => [
                $userInOrg, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 15 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, assessment stream status = in_validation and is submitted by him" => [
                $userInOrg, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 16 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, assessment stream status != in_validation and is not submitted by him" => [
                $userInOrg, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 17 - Test that validation to '/validate/{id}' is not allowed for a user, who is in the org, assessment stream status != in_validation and is submitted by him" => [
                $userInOrg, // user
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                [
                    'validation' => [
                        'remarks' => 'yes this looks good',
                        'ACCEPTED' => '',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider testEditValidationEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testEditValidationEndpointsDOA(
        User $user,
        Group $group,
        Project $project,
        AssessmentStatus $assessmentStreamStatus,
        bool $isInGroupProject,
        array $payload,
        int $expectedStatusCode
    ) {
        $container = self::getContainer();
        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        if ($isInGroupProject) {
            $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        }

        $makeAssessmentByProject = function (Project $project) use ($container) {
            $assessment = $container->get(AssessmentService::class)->createAssessment($project);

            return $assessment;
        };

        $setAssessmentStreamProps = function (AssessmentStream $assessmentStream, array $stages, AssessmentStatus $status) {
            /** @var Stage $stage */
            foreach ($stages as $stage) {
                $stage->setAssessmentStream($assessmentStream);
                $assessmentStream->addAssessmentStreamStage($stage);
            };
            $assessmentStream
                ->setStatus($status);

            return $assessmentStream;
        };

        $assessment = $makeAssessmentByProject($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);
        $setAssessmentStreamProps(
            $assessmentStream,
            [
                (new Evaluation()),
                (new Validation())->setSubmittedBy($user),
                (new Improvement()),
            ],
            $assessmentStreamStatus,
        );
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_validation_edit_validation", ['id' => $assessmentStream->getId()]), $payload);

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $responseCode);
    }

    private function testEditValidationEndpointDOAProvider(): array
    {
        $userInOrganizationAndRoleUser = (new UserBuilder())->build();
        $userInOrganization = (new UserBuilder())->withRoles([])->build();

        $group = (new GroupBuilder())->build();

        return [
            "Positive 1 - Test that editing validation at '/edit-validation/{id}' is allowed for a user, who is in the org, has role_user, assessment_status = validated, current_stage = improvement and is submitted by him" => [
                $userInOrganizationAndRoleUser, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::VALIDATED,
                true,
                [
                    'edit_validation' => [
                        'remarks' => 'this still looks good, but think about...',
                    ],
                ], // payload
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that editing validation at '/edit-validation/{id}' is not allowed for a user, who is in the org, has role_user, assessment_status = validated, current_stage = improvement and is not submitted by him" => [
                $userInOrganizationAndRoleUser, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::VALIDATED,
                false,
                [
                    'edit_validation' => [
                        'remarks' => 'this still looks good, but think about...',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that editing validation at '/edit-validation/{id}' is allowed for a user, who is in the org, has role_user, assessment_status != validated, current_stage = improvement and is submitted by him" => [
                $userInOrganizationAndRoleUser, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                false,
                [
                    'edit_validation' => [
                        'remarks' => 'this still looks good, but think about...',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 3 - Test that editing validation at '/edit-validation/{id}' is allowed for a user, who is in the org, assessment_status = validated, current_stage = improvement and is not submitted by him" => [
                $userInOrganization, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                false,
                [
                    'edit_validation' => [
                        'remarks' => 'this still looks good, but think about...',
                    ],
                ], // payload
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider testDeleteValidationRemarkEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testDeleteValidationRemarkEndpointsDOA(
        User $user,
        Group $group,
        Project $project,
        AssessmentStatus $assessmentStreamStatus,
        bool $isInGroupProject,
        int $expectedStatusCode
    ) {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        if ($isInGroupProject) {
            $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
            $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        }
        $makeAssessmentByProject = function (Project $project) use ($container) {
            $assessment = $container->get(AssessmentService::class)->createAssessment($project);

            return $assessment;
        };

        $setAssessmentStreamProps = function (AssessmentStream $assessmentStream, array $stages, AssessmentStatus $status) {
            /** @var Stage $stage */
            foreach ($stages as $stage) {
                $stage->setAssessmentStream($assessmentStream);
                $assessmentStream->addAssessmentStreamStage($stage);
            };
            $assessmentStream
                ->setStatus($status);

            return $assessmentStream;
        };
        $assessment = $makeAssessmentByProject($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);
        $setAssessmentStreamProps(
            $assessmentStream,
            [
                (new Evaluation()),
                (new Validation())->setSubmittedBy($user),
                (new Improvement()),
            ],
            $assessmentStreamStatus,
        );
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("DELETE", $this->urlGenerator->generate("app_validation_delete_validation_remark", ['id' => $assessmentStream->getLastStageByClass(Validation::class)->getId()]));

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $responseCode);

    }

    private function testDeleteValidationRemarkEndpointDOAProvider(): array
    {

        $userInOrganizationAndRoleUser = (new UserBuilder())->build();
        $userInOrganization = (new UserBuilder())->withRoles([])->build();
        $userNotInOrganizationRoleUser = (new UserBuilder())->build();

        $group = (new GroupBuilder())->build();

        return [
            "Positive 1 - Test that deleting validation at '/delete-validation-remark/{id}' is allowed for a user, who is in the org, has role_user, assessment_status = validated, current_stage = improvement and is submitted by him" => [
                $userInOrganizationAndRoleUser, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::VALIDATED,
                true,
                Response::HTTP_OK, // expected access
            ],
            "Negative 1 - Test that deleting validation at '/delete-validation-remark/{id}' is not allowed for a user, who is in the org, has role_user, assessment_status = validated, current_stage = improvement and is not submitted by him" => [
                $userInOrganizationAndRoleUser, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::VALIDATED,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 2 - Test that deleting validation at '/delete-validation-remark/{id}' is allowed for a user, who is in the org, has role_user, assessment_status != validated, current_stage = improvement and is submitted by him" => [
                $userInOrganizationAndRoleUser, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
            "Negative 3 - Test that deleting validation at '/delete-validation-remark/{id}' is allowed for a user, who is in the org, assessment_status = validated, current_stage = improvement and is not submitted by him" => [
                $userInOrganization, // user
                $group, // group
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_IMPROVEMENT,
                false,
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }


    /**
     * @dataProvider testSaveValidationRemarkProvider
     */
    public function testSaveValidationRemark(
        User $user,
        Project $project,
        Evaluation $evaluation,
        Validation $validation,
        AssessmentStatus $assessmentStreamStatus,
        string $insertedRemark,
        string $expectedRemark,
        bool $expectedRedirect,
        ?AssessmentStatus $expectedStreamStatus,
        ?string $expectedException
    ) {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $assessment = ($container->get(AssessmentService::class)->createAssessment($project));
        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(
            ["status" => \App\Enum\AssessmentStatus::NEW, "assessment" => $assessment]
        );
        $assessmentStream
            ->setStatus($assessmentStreamStatus)
            ->addAssessmentStreamStage($evaluation)
            ->addAssessmentStreamStage($validation);
        $evaluation->setAssessmentStream($assessmentStream);
        $validation->setAssessmentStream($assessmentStream);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $expectedRedirectUrl = null;
        if ($expectedRedirect) {
            $expectedRedirectUrl = "/validation/".$assessmentStream->getStream()->getId();
        }

        $this->client->loginUser($user, "boardworks");

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $this->client->request("POST", $this->urlGenerator->generate("app_validation_validate", ['id' => $assessmentStream->getId()]), [
            'validation' => [
                'remarks' => $insertedRemark,
                'SAVE' => '',
            ],
        ]);

        if ($expectedRedirect) {
            self::assertResponseRedirects($expectedRedirectUrl);
        }

        $container = self::getContainer();
        $validation = $container->get(ValidationRepository::class)->find($assessmentStream->getCurrentStage()->getId());

        self::assertEquals($expectedRemark, $validation->getComment());
        self::assertEquals($expectedStreamStatus, $assessmentStream->getStatus());

    }

    private function testSaveValidationRemarkProvider(): array
    {

        $userInOrganization1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string(), Role::VALIDATOR->string(), Role::MANAGER->string()])->build();
        $userInOrganization2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string(), Role::VALIDATOR->string()])->build();


        return [
            "Positive 1 - User manager tries to save remark " => [
                $userInOrganization1, // user
                (new ProjectBuilder())->build(),
                (new Evaluation())
                    ->setCompletedAt(new DateTime('now'))
                    ->setSubmittedBy($userInOrganization1)
                    ->setAssignedTo($userInOrganization1)
                ,
                (new Validation())
                    ->setStatus(\App\Enum\ValidationStatus::NEW)
                ,
                AssessmentStatus::IN_VALIDATION,
                "test remark", // attempted saved remark
                "test remark", // expected remark
                true, // expected redirect
                \App\Enum\AssessmentStatus::IN_VALIDATION, //expected status
                null, // expected exception
            ],
            "Negative 1 - Non manager tries to save remark on stream " => [
                $userInOrganization2, // user
                (new ProjectBuilder())->build(),
                (new Evaluation())
                    ->setCompletedAt(new DateTime('now'))
                    ->setSubmittedBy($userInOrganization2)
                    ->setAssignedTo($userInOrganization2)
                ,
                (new Validation())
                    ->setStatus(\App\Enum\ValidationStatus::ACCEPTED)
                ,
                AssessmentStatus::IN_VALIDATION,
                "test remark", // attempted saved remark
                "",  // expected remark
                false, // expected redirect
                null, //expected status
                AccessDeniedException::class, // expected exception
            ],
            "Negative 1 - User manager tries to save remark on wrong stream  state" => [
                $userInOrganization1, // user
                (new ProjectBuilder())->build(),
                (new Evaluation())
                    ->setCompletedAt(new DateTime('now'))
                    ->setSubmittedBy($userInOrganization1)
                    ->setAssignedTo($userInOrganization1)
                ,
                (new Validation())
                    ->setStatus(\App\Enum\ValidationStatus::ACCEPTED)
                ,
                AssessmentStatus::IN_VALIDATION,
                "test remark", // attempted saved remark
                "",  // expected remark
                false, // expected redirect
                null, //expected status
                SaveRemarkOnIncorrectStreamException::class, // expected exception
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider testValidateOtherGroupStreamProvider
     * @testdox Group voter check - attempt to validate stream to other group $_dataName
     */
    public function testValidateOtherGroupStream(User $user, Project $project, AssessmentStatus $assessmentStreamStatus)
    {
        $container = self::getContainer();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $assessment = $container->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = $container->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);
        $assessmentStream->setStatus($assessmentStreamStatus);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            "POST",
            $this->urlGenerator->generate("app_validation_validate", ['id' => $assessmentStream->getId()]),
            [
                "validation" => [
                    "remarks" => "fakeRemark",
                    "ACCEPTED" => "",
                ],
            ]
        );

    }

    private function testValidateOtherGroupStreamProvider(): array
    {

        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string()])->build();

        return [
            "Negative 1 - User2 from Group 2 tries to validate stream for Group 1, expect nothing to happen to the stream" => [
                $user2,
                (new ProjectBuilder())->build(),
                AssessmentStatus::IN_VALIDATION,
            ],
        ];
    }
}
