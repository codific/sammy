<?php

declare(strict_types=1);

namespace App\Tests\functional;

use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Validation;
use App\Enum\AssessmentStatus;
use App\Enum\ImprovementStatus;
use App\Enum\Role;
use App\Form\Application\ImprovementType;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentStreamRepository;
use App\Service\AssessmentService;
use App\Service\MetamodelService;
use App\Tests\_support\AbstractWebTestCase;
use App\Tests\builders\ProjectBuilder;
use App\Tests\builders\UserBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class StageControllerTest extends AbstractWebTestCase
{
    /**
     * @group pentestFindings22v1
     * @dataProvider testAssignUserToOtherGroupProvider
     * @testdox Group voter check - attempt to assign user to stream from other group $_dataName
     */
    public function testAssignUserToOtherGroup(User $userToLogin, User $userToBeAssigned, Project $project): void
    {
        $this->entityManager->persist($userToLogin);
        $this->entityManager->persist($userToBeAssigned);
        $this->entityManager->flush();

        $assessment = self::getContainer()->get(AssessmentService::class)->createAssessment($project);
        /** @var AssessmentStream $assessmentStream */
        $assessmentStream = self::getContainer()->get(AssessmentStreamRepository::class)->findOneBy(["assessment" => $assessment]);

        $evaluation = (new Evaluation())->setAssessmentStream($assessmentStream);
        $assessmentStream->addAssessmentStreamStage($evaluation);
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->flush();

        $this->expectException(AccessDeniedException::class);

        $this->client->loginUser($userToLogin, "boardworks");

        $this->client->request(
            "POST",
            $this->urlGenerator->generate("app_stage_ajaxSetAssignedTo", ['assessmentStream' => $assessmentStream->getId()]),
            [
                "name" => "assignedTo",
                "value" => $userToBeAssigned->getId(),
            ]
        );
    }

    private function testAssignUserToOtherGroupProvider(): array
    {
        $user1 = (new UserBuilder())->build();
        $user2 = (new UserBuilder())->build();


        return [
            "Negative 1 - User2 from Group 2 tries to finish stream for Group 1, expect nothing to happen to the stream" => [
                $user1,
                $user2,
                (new ProjectBuilder())->build(),
            ],
        ];
    }
}