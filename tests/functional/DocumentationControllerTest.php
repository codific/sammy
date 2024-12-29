<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\Remark;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\AssessmentStreamRepository;
use App\Service\AssessmentService;
use App\Service\MetamodelService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\GroupBuilder;
use App\Tests\builders\UserBuilder;
use App\Utils\Constants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DocumentationControllerTest extends AbstractWebTestCase
{
    /**
     * @group security
     * @group asvs
     * @dataProvider testProjectAccessProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that accessing '/documentation/show/{projectId}/{file}' $_dataName
     */
    public function testShowAccessControl(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project")->setMetamodel($metamodel);
        $group = (new GroupBuilder())->build();
        $group->addGroupGroupProject(
            (new GroupProject())
                ->setGroup($group)
                ->setProject($project)
        );

        self::getContainer()->get(AssessmentService::class)->createAssessment($project);

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir').'/private/projects/'.$project->getId();
        $fileName = "asdf.png";
        file_put_contents($path."/{$fileName}", "");

        $this->client->loginUser($testUser, "boardworks");
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_show',
                [
                    'id' => $project->getId(),
                    'file' => "asdf.png",
                ]
            )
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    private function testProjectAccessProvider(): array
    {
        $userInOrganizationAndInGroupAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndManagerInAnotherGroup = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndManagerNotInAnyGroups = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndInGroupAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userNotInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        // users without group
        $userInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInOrganizationAndRegularUser = (new UserBuilder())->withRoles([Role::USER->string()])->build();
        $userNotInOrganizationAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        return [
            "Positive 1 - is allowed for a user, who is in the org and has a manager role and is in the same group as the project" => [
                $userInOrganizationAndInGroupAndManager,
                Response::HTTP_OK,
            ],
            "Positive 2 - is allowed for a user, who is in the org and has a manager role, but he is not in the current group project" => [
                $userInOrganizationAndManagerInAnotherGroup,
                Response::HTTP_OK,
            ],
            // (bugfix, see commit: {84b106eb} for more info)
            "Positive 3 - is allowed for a user, who is in the org and has a manager role, but he is not a part of any groups" => [
                $userInOrganizationAndManagerNotInAnyGroups,
                Response::HTTP_OK,
            ],
            "Negative 1 - is not allowed for a regular user, who is in the same org and in the same group" => [
                $userInOrganizationAndInGroupAndRegularUser,
                Response::HTTP_FORBIDDEN,
            ],
            "Negative 3 - is not allowed for a regular user, who is in the same org, but not in the group" => [
                $userInOrganizationAndRegularUser,
                Response::HTTP_FORBIDDEN,
            ],
        ];
    }

    /**
     * @group security
     * @group asvs
     * @dataProvider testProjectAccessProvider
     * @testdox Access Control(v4.0.3-4.2.1) Test that accessing '/documentation/preview/{projectId}/{file}' $_dataName
     */
    public function testPreviewAccessControl(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $metamodel = self::getContainer()->get(MetamodelService::class)->getSAMM();
        $project = (new Project())->setName("test project")->setMetamodel($metamodel);
        $group = (new GroupBuilder())->build();
        $group->addGroupGroupProject(
            (new GroupProject())
                ->setGroup($group)
                ->setProject($project)
        );

        self::getContainer()->get(AssessmentService::class)->createAssessment($project);

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir').'/private/projects/'.$project->getId();
        $fileName = "asdf.png";
        file_put_contents($path."/{$fileName}", "");

        $this->client->loginUser($testUser, "boardworks");
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_preview',
                [
                    'id' => $project->getId(),
                    'file' => "asdf.png",
                ]
            )
        );

        self::assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function testShowWorksWithDifferentMimeTypes(): void
    {
        $entities = $this->setupUserWithProject();
        $project = $entities['project'];
        $user = $entities['user'];
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir').'/private/projects/'.$project->getId();

        copy("tests/files_for_tests/Blank.png", "{$path}/Blank.png");
        copy("tests/files_for_tests/empty_file_template.xlsx", "{$path}/empty_file_template.xlsx");
        file_put_contents("{$path}/text.txt", "asdf");

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_show',
                [
                    'id' => $project->getId(),
                    'file' => "Blank.png",
                ]
            )
        );

        $contentType = $this->client->getResponse()->headers->get("content-type");
        self::assertEquals("image/png", $contentType);

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_show',
                [
                    'id' => $project->getId(),
                    'file' => "empty_file_template.xlsx",
                ]
            )
        );

        $contentType = $this->client->getResponse()->headers->get("content-type");
        self::assertEquals($contentType, "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_show',
                [
                    'id' => $project->getId(),
                    'file' => "text.txt",
                ]
            )
        );

        $contentType = $this->client->getResponse()->headers->get("content-type");
        self::assertEquals("text/plain; charset=UTF-8", $contentType);
    }

    public function testShowWorksWithNestedFolders(): void
    {
        $entities = $this->setupUserWithProject();
        $project = $entities['project'];
        $user = $entities['user'];
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir').'/private/projects/'.$project->getId()."/nesting1/nesting2/nesting3";
        @mkdir($path, recursive: true);

        copy("tests/files_for_tests/Blank.png", "{$path}/Blank.png");

        $this->client->loginUser($user, "boardworks");
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_show',
                [
                    'id' => $project->getId(),
                    'file' => "nesting1/nesting2/nesting3/Blank.png",
                ]
            )
        );

        $contentType = $this->client->getResponse()->headers->get("content-type");
        self::assertEquals($contentType, "image/png");
    }

    public function testPreviewWorksWithDifferentMimeTypes(): void
    {
        $entities = $this->setupUserWithProject();
        $project = $entities['project'];
        $user = $entities['user'];
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir').'/private/projects/'.$project->getId();

        copy("tests/files_for_tests/Blank.png", "{$path}/Blank.png");
        copy("tests/files_for_tests/empty_file_template.xlsx", "{$path}/empty_file_template.xlsx");
        file_put_contents("{$path}/text.txt", "asdf");

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_preview',
                [
                    'id' => $project->getId(),
                    'file' => "Blank.png",
                ]
            )
        );
        self::assertSelectorExists("img[src='/documentation/show/{$project->getId()}/Blank.png']");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_preview',
                [
                    'id' => $project->getId(),
                    'file' => "empty_file_template.xlsx",
                ]
            )
        );
        self::assertSelectorTextContains("p", "Preview is not available for this file type");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_preview',
                [
                    'id' => $project->getId(),
                    'file' => "text.txt",
                ]
            )
        );
        self::assertSelectorTextContains("p", "asdf");
    }

    public function testPreviewWorksWithNestedFolders(): void
    {
        $entities = $this->setupUserWithProject();
        $project = $entities['project'];
        $user = $entities['user'];
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir').'/private/projects/'.$project->getId()."/nesting1/nesting2/nesting3";
        @mkdir($path, recursive: true);

        copy("tests/files_for_tests/Blank.png", "{$path}/Blank.png");

        $this->client->loginUser($user, "boardworks");
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_preview',
                [
                    'id' => $project->getId(),
                    'file' => "nesting1/nesting2/nesting3/Blank.png",
                ]
            )
        );
        self::assertSelectorExists("img[src='/documentation/show/{$project->getId()}/nesting1/nesting2/nesting3/Blank.png']");
    }

    /**
     * @group security
     * @dataProvider pathTraversalDataProvider
     */
    public function testPreviewForPathTraversal(string $travers): void
    {
        $entities = $this->setupUserWithProject();
        $project = $entities['project'];
        $user = $entities['user'];
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // project the user is not associated with
        $nonAssociatedProject = (new Project())->setName("test project");
        $this->entityManager->persist($nonAssociatedProject);
        $this->entityManager->flush();

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir')."/private/projects/{$nonAssociatedProject->getId()}";
        @mkdir($path);
        copy("tests/files_for_tests/Blank.png", "{$path}/Blank.png");

        $file = "$travers.{$nonAssociatedProject->getId()}/Blank.png";

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_preview',
                [
                    'id' => $project->getId(),
                    'file' => $file,
                ]
            )
        );
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @group security
     * @dataProvider pathTraversalDataProvider
     */
    public function testShowForPathTraversal(string $travers): void
    {
        $entities = $this->setupUserWithProject();
        $project = $entities['project'];
        $user = $entities['user'];
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // project the user is not associated with
        $nonAssociatedProject = (new Project())->setName("test project");
        $this->entityManager->persist($nonAssociatedProject);
        $this->entityManager->flush();

        $parameterBag = static::getContainer()->get('parameter_bag');
        $path = $parameterBag->get('kernel.project_dir')."/private/projects/{$nonAssociatedProject->getId()}";
        @mkdir($path);
        copy("tests/files_for_tests/Blank.png", "{$path}/Blank.png");

        $file = "$travers.{$nonAssociatedProject->getId()}/Blank.png";

        $this->client->loginUser($user, "boardworks");

        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_show',
                [
                    'id' => $project->getId(),
                    'file' => $file,
                ]
            )
        );
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function pathTraversalDataProvider(): array
    {
        return [
            "../nonAssociatedProject->getId()/Blank.png" => [
                "../",
            ],
            "%2E%2E%2FnonAssociatedProject->getId()/Blank.png" => [
                "%2E%2E%2F",
            ],
            "....//nonAssociatedProject->getId()/Blank.png" => [
                "....//",
            ],
            "%2E%2%E2E%2E%2F%2FnonAssociatedProject->getId()/Blank.png" => [
                "%2E%2%E2E%2E%2F%2F",
            ],
        ];
    }

    /**
     * @group asvs
     * @group security
     * @dataProvider inputFieldHTMLSanitizationProvider
     * @testdox Input sanitization(v4.0.3-5.2.1) Test that untrusted HTML input is properly sanitized at '/save-documentation'.
     */
    public function testInputFieldHTMLIsSanitizedAtDocumentAssessment(
        string $maliciousInsert,
        string $expectedInsertAfterSanitization
    ) {
        //Arrange
        $group = (new Group());
        ($project = new Project())->setName("test project");
        $organizationGroup = (new Group())->setName("test group");
        $organizationGroup->addGroupGroupProject((new GroupProject())->setGroup($organizationGroup)->setProject($project));
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $project->setAssessment($assessment);
        $project->setMetamodel($this->entityManager->getReference(Metamodel::class, Constants::SAMM_ID));

        $assessmentStream = self::getContainer()
            ->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $testUser = new User();
        $testUser
            ->setRoles([Role::USER->string(), Role::MANAGER->string()])
            ->setSecretKey("MFZWIZDEMRSQ====")
            ->setAgreedToTerms(true)
            ->setEmail(bin2hex(random_bytes(5))."@test.test")
            ->setPassword('$2a$12$yNaZmq0qLazJUMsiIbsO6eXW9v2uYilotB0uNFclWTywNOrpZLa9e') //admin123
            ->addUserGroupUser((new GroupUser())->setUser($testUser)->setGroup($organizationGroup));

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $this->client->loginUser($testUser, "boardworks");

        //Act
        $this->client->request("POST", $this->urlGenerator->generate("app_documentation_save_documentation", ['id' => $assessmentStream->getId()]), [
            "documentation" => [
                "text" => $maliciousInsert,
            ],
        ]);

        //Assert
        $actualRemarkAfterInsert = $this->entityManager->getRepository(Remark::class)->findOneBy(['text' => $expectedInsertAfterSanitization]);

        self::assertEquals($expectedInsertAfterSanitization, $actualRemarkAfterInsert->getText());
    }

    public function inputFieldHTMLSanitizationProvider(): array
    {
        return [
            "Test with inserted <script> tag" => [
                "this is a test insert1 -> <script>alert('malicious alert')</script>", // malicious insert
                "this is a test insert1 -> ", // expected insert after sanitization
            ],
            "Test with inserted <a> tag " => [
                "this is a test insert2 -> <a href=\"www.google.com\">click here </a>", // html insert
                "this is a test insert2 -> <a href=\"www.google.com\">click here </a>", // expected input after sanitzation
            ],
        ];
    }


    /**
     * @group asvs
     * @group security
     * @dataProvider documentationPageEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testDocumentationPageEndpointsDOA(User $user, bool $expectedAccess): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $group = (new Group());
        $project = (new Project());
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        $user->addUserGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $this->entityManager->flush();

        if ($expectedAccess) {
            $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        }
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));

        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $this->entityManager->flush();

        if (!$expectedAccess) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("GET", $this->urlGenerator->generate("app_documentation_documentation_page", ['id' => $assessmentStream->getId()]));

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals(200, $responseCode);
    }

    public function documentationPageEndpointDOAProvider(): array
    {
        $user = (new UserBuilder())->build();

        return [
            "Positive 1 - Test that access to '/documentation-page/{id}' is allowed for a user who is in the organization" => [
                $user, //
                true, // expected access
            ],
        ];
    }

    /**
     * @group asvs
     * @group security
     * @dataProvider saveDocumentationEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testSaveDocumentationEndpointsDOA(User $user, int $expectedStatusCode): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $project = (new Project())->setName("test project");
        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        $group = (new Group());
        $group->addGroupGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $group->addGroupGroupProject((new GroupProject())->setProject($project)->setGroup($group));
        $user->addUserGroupUser((new GroupUser())->setUser($user)->setGroup($group));
        $this->entityManager->flush();

        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $payload = [
            'documentation' => [
                'text' => 'testdocument',
                'assessmentStream' => $assessmentStream->getId(),
            ],
        ];

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->client->loginUser($user, "boardworks");

        $this->client->request("POST", $this->urlGenerator->generate("app_documentation_save_documentation", ['id' => $assessmentStream->getId()]), $payload);

        $responseCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $responseCode);
    }

    public function saveDocumentationEndpointDOAProvider(): array
    {
        $userRoleUser = (new UserBuilder())->build();
        $userRoleUserEvaluatorValidatorImproverManager = (new UserBuilder())->withRoles([
            Role::USER->string(),
            Role::EVALUATOR->string(),
            Role::VALIDATOR->string(),
            Role::IMPROVER->string(),
            Role::MANAGER->string(),
        ])->build();

        $userRoleUserAndEvaluator = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();

        $userRoleUserAndValidator = (new UserBuilder())->withRoles([Role::USER->string(), Role::VALIDATOR->string()])->build();

        $userRoleUserAndImprover = (new UserBuilder())->withRoles([Role::USER->string(), Role::IMPROVER->string()])->build();

        $userRoleUserAndManager = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        $user = (new UserBuilder())->build();


        return [
            "Positive 1 - Test that save to '/save-documentation/{id}' is allowed for user who has role user and is evaluator,validator,improver,manager" => [
                $userRoleUserEvaluatorValidatorImproverManager, // user
                Response::HTTP_OK, // expected status code
            ],
            "Positive 2 - Test that save to '/save-documentation/{id}' is allowed for user who has role user and is evaluator" => [
                $userRoleUserAndEvaluator, // user
                Response::HTTP_OK, // expected status code
            ],
            "Positive 3 - Test that save to '/save-documentation/{id}' is allowed for user who has role user and is validator" => [
                $userRoleUserAndValidator, // user
                Response::HTTP_OK, // expected status code
            ],
            "Positive 4 - Test that save to '/save-documentation/{id}' is allowed for user who has role user and is improver" => [
                $userRoleUserAndImprover, // user
                Response::HTTP_OK, // expected status code
            ],
            "Positive 5 - Test that save to '/save-documentation/{id}' is allowed for user who has role user and is manager" => [
                $userRoleUserAndManager, // user
                Response::HTTP_OK, // expected status code
            ],
            "Negative 1 - Test that save to '/save-documentation/{id}' is not allowed for user who has role user and does not have any other roles" => [
                $userRoleUser, // user
                Response::HTTP_FORBIDDEN, // expected status code
            ],
            "Negative 2 - Test that save to '/save-documentation/{id}' is not allowed for user who does not have role user and does not have any other roles" => [
                $user, // user
                Response::HTTP_FORBIDDEN, // expected status code
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider testDocumentationPageAccessOtherGroupProvider
     * @testdox Group voter check - attempt to access group data $_dataName
     */
    public function testDocumentationPageAccessOtherGroup(User $testUser, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $project = (new Project());
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($testUser, "boardworks");
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate(
                'app_documentation_documentation_page',
                [
                    'id' => $assessmentStream->getId(),
                ]
            )
        );
    }

    private function testDocumentationPageAccessOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();


        return [
            "Positive 1 - User1 from Group 1 tries to see documentation of Group 1" => [
                $user1, // user
                Response::HTTP_OK, //expected code
            ],
            "Negative 1 - User2 from Group 2 tries to see documentation of Group 1" => [
                $user2, // user
                Response::HTTP_FORBIDDEN, //expected code
            ],
        ];
    }

    /**
     * @group pentestFindings22v1
     * @dataProvider saveDocumentationToOtherGroupProvider
     * @testdox Group voter check - attempt to save remark to other group $_dataName
     */
    public function testSaveDocumentationToOtherGroup(User $testUser): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $project = (new Project());
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $project->setMetamodel(self::getContainer()->get(MetamodelService::class)->getSAMM());
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
            ->findOneBy([
                "assessment" => $assessment,
            ]);

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($testUser, "boardworks");
        $this->client->request(
            Request::METHOD_POST,
            $this->urlGenerator->generate(
                'app_documentation_save_documentation',
                [
                    'id' => $assessmentStream->getId(),
                ]
            ),
            [
                'documentation' => [
                    'ckeditor' => 'fakeText',
                ],
            ]
        );
    }

    public function saveDocumentationToOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();
        $user2 = (new UserBuilder())->withRoles([Role::USER->string(), Role::EVALUATOR->string()])->build();


        return [
            "Positive 1 - User1 from Group 1 tries to save documentation to Group 1" => [
                $user1, // user
                Response::HTTP_OK, //expected code
            ],
            "Negative 1 - User2 from Group 2 tries to save documentation to Group 1" => [
                $user2, // user
                Response::HTTP_FORBIDDEN, //expected code
            ],
        ];
    }

    private function setupUserWithProject(): array
    {
        $user = (new UserBuilder())->build();
        $project = (new Project())->setName("test project");
        $group = (new GroupBuilder())->build();
        $group->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject($project));
        $user->addUserGroupUser(
            (new GroupUser())
                ->setGroup($group)->setUser($user)
        );

        $container = static::getContainer();
        $container->get(AssessmentService::class)->createAssessment($project);

        return [
            "user" => $user,
            "project" => $project,
        ];
    }
}