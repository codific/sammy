<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\Role;
use App\Enum\ValidationStatus;
use App\Repository\AssessmentStreamRepository;
use App\Service\AssessmentService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\GroupBuilder;
use App\Tests\builders\ProjectBuilder;
use App\Tests\builders\UserBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SwitchProjectControllerTest extends AbstractWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSwitchProjectAccessInTheSameGroup(): void
    {
        // Arrange
        $user = (new UserBuilder($this->entityManager))->build();
        $project = (new Project())->setName("allowed project");
        $group = (new GroupBuilder($this->entityManager))->withName("allowed group")->build();
        $groupProject = (new GroupProject())->setProject($project)->setGroup($group);
        $groupUser = (new GroupUser())->setGroup($group)->setUser($user);

        $this->entityManager->persist($project);
        $this->entityManager->persist($groupProject);
        $this->entityManager->persist($groupUser);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        $httpReferer = 'http://localhost/profile/profile';
        // Act
        $this->client->request("POST", "/switch/project/".$project->getId(), [], [], [
            'HTTP_REFERER' => $httpReferer,
        ]);

        // Assert
        self::assertResponseRedirects("/dashboard");
    }

    public function testSwitchProjectAccessInTheDifferentGroup()
    {
        // Arrange
        $user = (new UserBuilder($this->entityManager))->build();
        $project = (new Project())->setName("allowed project");
        $group = (new GroupBuilder($this->entityManager))->withName("allowed group")->build();
        $groupProject = (new GroupProject())->setProject($project)->setGroup($group);

        // NOTE:
        // Explicitly commented this out
        // to make it clear that the user we've created is not part of the group
        //$groupUser = (new GroupUser())->setGroup($group)->setUser($user);

        $this->entityManager->persist($project);
        $this->entityManager->persist($groupProject);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        // Assert
        $this->expectException(AccessDeniedException::class);

        // Act
        $this->client->request("POST", "/switch/project/".$project->getId(), [], [], [
            'HTTP_REFERER' => 'http://localhost/profile/profile',
        ]);
    }


    /**
     * @dataProvider switchProjectRefererDataProvider
     * @param string $refererHeader
     * @param string $expectedRedirect
     * @throws \Exception
     */
    public function testSwitchProjectReferer(string $refererHeader, string $expectedRedirect): void
    {
        // Arrange
        $user = (new UserBuilder($this->entityManager))->build();
        $project = (new Project())->setName("allowed project");
        $group = (new GroupBuilder($this->entityManager))->withName("allowed group")->build();
        $groupProject = (new GroupProject())->setProject($project)->setGroup($group);
        $groupUser = (new GroupUser())->setGroup($group)->setUser($user);

        $this->entityManager->persist($project);
        $this->entityManager->persist($groupProject);
        $this->entityManager->persist($groupUser);
        $this->entityManager->flush();

        $this->client->loginUser($user, "boardworks");

        // Act
        $this->client->request("POST", "/switch/project/".$project->getId(), [], [], [
            'HTTP_REFERER' => $refererHeader,
        ]);

        // Assert
        self::assertResponseRedirects($expectedRedirect);
    }

    private function switchProjectRefererDataProvider(): array
    {
        return [
            "Attempt to access project in same group with referer header same website, expect success and redirect to referer" => [
                'http://localhost/profile/profile',
                '/dashboard',
            ],
            "Attempt to access project in same group with referer header different website, expect success and redirect to dashboard (and not the other website)" => [
                'https://some-other-website',
                '/dashboard',
            ],
        ];
    }

    /**
     * @group asvs
     * @dataProvider testSwitchProjectEndpointDOAProvider
     * @testdox Access Control(v4.0.3-4.2.1) $_dataName
     */
    public function testSwitchProjectEndpointDOA(User $testUser, Project $project, Group $group, array $headers, int $expectedStatusCode): void
    {
        $this->entityManager->persist($testUser);
        $this->entityManager->persist($group);
        $this->entityManager->persist($project);
        $metamodel = (new Metamodel());
        $this->entityManager->persist($metamodel);

        $group->addGroupGroupProject((new GroupProject())->setGroup($group)->setProject($project));
        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        $assessment->setProject($project);
        $project->setAssessment($assessment);
        $project->setMetamodel($metamodel);

        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)
        ->findOneBy([
            "assessment" => $assessment,
        ]);
        /** @var \App\Entity\AssessmentStream $assessmentStream */
        $assessmentStream->setStatus(AssessmentStatus::VALIDATED);
        $validation = (new Validation())->setAssessmentStream($assessmentStream)->setCompletedAt(new \DateTime('yesterday'))->setStatus(ValidationStatus::ACCEPTED);

        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($validation);
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();

        if ($expectedStatusCode === Response::HTTP_FORBIDDEN) {
            $this->expectException(AccessDeniedException::class);
        }

        $this->loginUser($testUser, "boardworks");

        $this->client->request("POST", "/switch/project/".$project->getId(), [
            "token" => $this->getToken('switch_project'),
        ], [], $headers);

        $actualStatusCode = $this->client->getResponse()->getStatusCode();
        self::assertEquals($expectedStatusCode, $actualStatusCode);
    }

    private function testSwitchProjectEndpointDOAProvider(): array
    {
        $userManagerAndInGroup = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userManagerNotInGroup = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();
        $userInGroup = (new UserBuilder())->build();
        $userInAnotherGroup = (new UserBuilder())->build();

        $group = (new GroupBuilder())->build();

        $userManagerAndInGroup->addUserGroupUser((new GroupUser())->setGroup($group)->setUser($userManagerAndInGroup));
        $userInGroup->addUserGroupUser((new GroupUser())->setGroup($group)->setUser($userInGroup));
        $userInAnotherGroup->addUserGroupUser((new GroupUser())->setGroup((new Group()))->setUser($userInGroup));


        return [
            "Positive 1 - Test that switching project at '/switch/project/{id}' is allowed for a user, who has a manager role and is in the same group as the project" => [
                $userManagerAndInGroup, // user
                (new ProjectBuilder())->build(), // project
                $group, // group
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/assessment/info",
                ], // headers
                Response::HTTP_FOUND, // expected access
            ],
            "Positive 2 - Test that switching project at '/switch/project/{id}' is allowed for a user, who has a manager role, but he is not a part of any groups" => [
                $userManagerNotInGroup, // user
                (new ProjectBuilder())->build(), // project
                $group, // group
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/assessment/info",
                ], // headers
                Response::HTTP_FOUND, // expected access
            ],
            "Positive 3 - Test that switching project at '/switch/project/{id}' is allowed for a regular user, who is in the same group" => [
                $userInGroup, // user
                (new ProjectBuilder())->build(), // project
                $group, // group
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/assessment/info",
                ], // headers
                Response::HTTP_FOUND, // expected access
            ],
            "Negative 3 - Test that switching project at '/switch/project/{id}' is not allowed for a regular user, who is not in the group" => [
                $userInAnotherGroup, // user
                (new ProjectBuilder())->build(), // project
                $group, // group
                [
                    "HTTP_REFERER" => "https://127.0.0.1:8000/assessment/info",
                ], // headers
                Response::HTTP_FORBIDDEN, // expected access
            ],
        ];
    }
}
