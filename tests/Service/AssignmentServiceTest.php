<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Stage;
use App\Entity\User;
use App\Service\AssignmentService;
use App\Tests\_support\AbstractKernelTestCase;

class AssignmentServiceTest extends AbstractKernelTestCase
{
    private AssignmentService $assignmentService;

    public function setUp(): void
    {
        parent::setUp();
        $this->assignmentService = self::getContainer()->get(AssignmentService::class);
    }

    /**
     * @dataProvider testAddAssignmentProvider
     * @testdox $_dataName
     */
    public function testAddAssignment(Assignment $assignment, Stage $stage, User $user, User $userWhoAssigned, string $remark): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->persist($stage);
        $this->entityManager->persist($user);
        $this->entityManager->persist($userWhoAssigned);
        $this->entityManager->flush();

        $this->assignmentService->addAssignment($assignment, $stage, $user, $userWhoAssigned, $remark);

        $result = $this->entityManager->getRepository(Assignment::class)->findOneBy(['remark' => $remark]);

        self::assertEquals($assignment->getId(), $result->getId());
        self::assertEquals($stage, $result->getStage());
        self::assertEquals($user, $result->getUser());
        self::assertEquals($userWhoAssigned, $result->getAssignedBy());
        self::assertEquals($remark, $result->getRemark());
    }

    private function testAddAssignmentProvider(): array
    {
        return [
            "Test 1 - that the assignment is properly added by addAssignment" => [
                (new Assignment()), // assignment
                (new Evaluation()), // stage
                (new User()), // user
                (new User()), // user who assigns
                "remark_".bin2hex(random_bytes(5)), //remark
            ],
            "Test 2 - that the assignment is properly added by addAssignment" => [
                (new Assignment()), // assignment
                (new Evaluation()), // stage
                (new User()), // user
                (new User()), // user who assigns
                "remark_".bin2hex(random_bytes(5)), //remark
            ]
        ];
    }

    /**
     * @dataProvider testDeleteAssignmentProvider
     * @testdox $_dataName
     */
    public function testDeleteAssignmentAssignment(Assignment $assignment): void
    {
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        $resultBefore = $this->entityManager->getRepository(Assignment::class)->findOneBy(['id'=>$assignment->getId()]);

        self::assertNull($resultBefore->getDeletedAt());

        $this->assignmentService->deleteAssignment($assignment);

        $resultAfter = $this->entityManager->getRepository(Assignment::class)->findOneBy(['id'=>$assignment->getId()]);

        self::assertNotNull($resultAfter->getDeletedAt());
    }

    private function testDeleteAssignmentProvider(): array
    {
        return [
            "Test 1 - that the assignment is properly deleted by deleteAssignment" => [
                (new Assignment()), // assignment
            ],
            "Test 2 - that the assignment is properly deleted by deleteAssignment" => [
                (new Assignment()), // assignment
            ]
        ];
    }

    /**
     * @dataProvider testDeleteStageAssignmentsProvider
     * @testdox $_dataName
     */
    public function testDeleteStageAssignments(User|null $user, Stage $stage, Assignment $assignmentToInsert, bool $shouldBeDeleted): void
    {
        $assignmentToInsert->setStage($stage);

        $this->entityManager->persist($assignmentToInsert);
        $this->entityManager->persist($stage);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $assignmentsBefore = $this->entityManager->getRepository(Assignment::class)->findBy(['stage' => $stage]);

       foreach ($assignmentsBefore as $assignment) {
           self::assertNull($assignment->getDeletedAt());
        }

        $this->assignmentService->deleteStageAssignments($stage, $user);

        $assignmentsAfter = $this->entityManager->getRepository(Assignment::class)->findBy(['stage' => $stage]);

        foreach ($assignmentsAfter as $assignment) {
            if ($shouldBeDeleted) {
                self::assertNotNull($assignment->getDeletedAt());
            } else {
                self::assertNull($assignment->getDeletedAt());
            }
        }
    }

    private function testDeleteStageAssignmentsProvider(): array
    {
        return [
            "Positive 1 - Test that assignments are being deleted by deleteStageAssignments" => [
                (new User()), // user
                (new Evaluation()), // stage
                (new Assignment()), // inserted assignment
                true, // should be deleted
            ],
            "Positive 2 - Test that assignments are being deleted by deleteStageAssignments" => [
                (new User()), // user
                (new Evaluation()), // stage
                (new Assignment()), // inserted assignment
                true, // should be deleted
            ],
            "Negative 1 - Test that assignments are not being deleted by deleteStageAssignments" => [
                $user = (new User()), // user
                (new Evaluation()), // stage
                (new Assignment())->setUser($user), // inserted assignment
                false, // should be deleted
            ],
            "Negative 2 - Test that assignments are not being deleted by deleteStageAssignments" => [
                $user2 = (new User()), // user
                (new Evaluation()), // stage
                (new Assignment())->setUser($user2), // inserted assignment
                false, // should be deleted
            ]
        ];
    }

    /**
     * @dataProvider testCompleteStageAssignmentsProvider
     * @testdox $_dataName
     */
    public function testCompleteStageAssignments(Stage $stage, array $assignmentsToInsert, bool $shouldBeCompleted): void
    {
        foreach($assignmentsToInsert as $assignment) {
            $this->entityManager->persist($assignment);
        }
        $this->entityManager->persist($stage);
        $this->entityManager->flush();

        foreach ($assignmentsToInsert as $assignment) {
            self::assertNull($assignment->getCompletedAt());
        }

        $this->assignmentService->completeStageAssignments($stage);

        $assignmentsAfter = $this->entityManager->getRepository(Assignment::class)->findBy(['stage' => $stage]);

        foreach ($assignmentsAfter as $assignment) {
            if ($shouldBeCompleted) {
                self::assertNotNull($assignment->getCompletedAt());
            } else {
                self::assertNull($assignment->getCompletedAt());
            }
        }
    }

    private function testCompleteStageAssignmentsProvider(): array
    {
        return [
            "Positive 1 - Test that assignments are being completed with certain stage by completeStageAssignments" => [
                $stage = (new Stage()), // stage
                [
                    (new Assignment())->setStage($stage),
                    (new Assignment())->setStage($stage),
                    (new Assignment())->setStage($stage)
                ], // assignments to insert
                true, // should be completed
            ],
            "Negative 1 - Test that assignments are not being completed with certain stage by completeStageAssignments" => [
                (new Stage()), // stage
                [
                    (new Assignment())->setStage(new Evaluation()),
                    (new Assignment())->setStage(new Evaluation()),
                    (new Assignment())->setStage(new Evaluation())
                ], // assignments to insert
                false, // should be completed
            ],
            "Negative 2 - Test that assignments are not being completed with certain stage by completeStageAssignments" => [
                (new Stage()), // stage
                [
                    (new Assignment())->setStage(new Evaluation()),
                    (new Assignment())->setStage(new Evaluation()),
                    (new Assignment())->setStage(new Evaluation())
                ], // assignments to insert
                false, // should be completed
            ],
        ];
    }


    /**
     * @dataProvider testGetStageAssignmentProvider
     * @testdox $_dataName
     */
    public function testGetStageAssignment(Stage $stage, Assignment $assignmentToInsert, bool $shouldBeReturned): void
    {
        $this->entityManager->persist($assignmentToInsert);
        $this->entityManager->persist($stage);
        $this->entityManager->flush();

        $result = $this->assignmentService->getStageAssignment($stage);

        if ($shouldBeReturned) {
            self::assertEquals($assignmentToInsert, $result);
        } else {
            self::assertNull($result);
        }
    }

    private function testGetStageAssignmentProvider(): array
    {
        return [
            "Positive 1 - Test that assignment with certain stage is being returned by getStageAssignment" => [
                $stage = (new Stage()), // stage
                (new Assignment())->setStage($stage), // assignment to insert
                true, // should be returned
            ],
            "Negative 1 - Test that assignment with certain stage is not being returned by getStageAssignment" => [
                (new Stage()), // stage
                (new Assignment())->setStage(new Stage()), // assignment to insert
                false, // should be returned
            ],
            "Negative 2 - Test that assignment with certain stage is not being returned by getStageAssignment" => [
                (new Stage()), // stage
                (new Assignment())->setStage(new Stage()), // assignment to insert
                false, // should be returned
            ],
        ];
    }

    /**
     * @dataProvider testGetAssessmentStreamCurrentAssignmentsProvider
     * @testdox $_dataName
     */
    public function testGetAssessmentStreamCurrentAssignments(array $insertedAssessmentStreams, array $assignmentsToInsert, bool $shouldBeReturned): void
    {
        foreach ($insertedAssessmentStreams as $insertedAssessmentStream){
            $this->entityManager->persist($insertedAssessmentStream);
        }
        foreach ($assignmentsToInsert as $insertedAssignment) {
            $this->entityManager->persist($insertedAssignment);
        }
        $this->entityManager->flush();

        $result = $this->assignmentService->getAssessmentStreamCurrentAssignments($insertedAssessmentStreams);

        $insertedAssignmentsIds = [];
        foreach ($assignmentsToInsert as $insertedAssignment){
            $insertedAssignmentsIds[] = $insertedAssignment->getId();
        }
        if ($shouldBeReturned) {
            foreach ($result as $returnedAssignment){
                self::assertContains($returnedAssignment->getId(), $insertedAssignmentsIds);
            }
        } else {
            self::assertCount(0, $result);
        }
    }

    private function testGetAssessmentStreamCurrentAssignmentsProvider(): array
    {
        return [
            "Positive 1 - Test that assignments who are active and have certain assessment stream are returned by getAssessmentStreamCurrentAssignments" => [
                [
                    $assessmentStream1 = (new AssessmentStream()),
                    $assessmentStream2 = (new AssessmentStream()),
                ], // assessment streams
                [
                    (new Assignment())->setStage((new Evaluation())->setAssessmentStream($assessmentStream1)),
                    (new Assignment())->setStage((new Evaluation())->setAssessmentStream($assessmentStream2))
                ], // assignments to insert
                true, // should be returned
            ],
            "Negative 1 - Test that assignments who are not active and have certain assessment stream are not returned by getAssessmentStreamCurrentAssignments" => [
                [
                    $assessmentStream3 = (new AssessmentStream()),
                    $assessmentStream4 = (new AssessmentStream()),
                ], // assessment streams
                [
                    (new Assignment())->setStage(((new Evaluation())->setCompletedAt(new \DateTime('yesterday')))->setAssessmentStream($assessmentStream3)),
                    (new Assignment())->setStage(((new Evaluation())->setCompletedAt(new \DateTime('yesterday')))->setAssessmentStream($assessmentStream4))
                ], // assignments to insert
                false, // should be returned
            ],
            "Negative 2 - Test that assignments who are active and have other assessment stream are not returned by getAssessmentStreamCurrentAssignments" => [
                [
                    (new AssessmentStream()),
                    (new AssessmentStream()),
                ], // assessment streams
                [
                    (new Assignment())->setStage((new Evaluation())->setAssessmentStream(new AssessmentStream())),
                    (new Assignment())->setStage((new Evaluation())->setAssessmentStream(new AssessmentStream()))
                ], // assignments to insert
                false, // should be returned
            ],
        ];
    }


    /**
     * @dataProvider testGetAssessmentsGroupedByUsersProvider
     * @testdox $_dataName
     */
    public function testGetAssessmentsGroupedByUsers(array $insertedAssignments, array $users, bool $shouldBeReturned): void
    {
        foreach ($insertedAssignments as $insertedAssignment) {
            $this->entityManager->persist($insertedAssignment);
        }
        foreach ($users as $user) {
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        $result = $this->assignmentService->getAssessmentsGroupedByUsers($users);

        $resultAssignmentIds = [];
        foreach ($result as $returnedAssignments) {
            foreach ($returnedAssignments as $assignment) {
                $resultAssignmentIds[] = $assignment->getId();
            }
        }
        if ($shouldBeReturned) {
            foreach ($insertedAssignments as $insertedAssignment) {
                self::assertContains($insertedAssignment->getId(), $resultAssignmentIds);
            }
        } else {
            self::assertCount(0, $result);
        }
    }

    private function testGetAssessmentsGroupedByUsersProvider(): array
    {
        $user1 = (new User());
        $user2 = (new User());
        $user3 = (new User());
        $user4 = (new User());
        return [
            "Positive 1 - Test that assignments with certain user and not active being returned by getAssessmentsGroupedByUsers" => [
                [
                    $assignment1 = (new Assignment())->setUser($user1)->setStage(new Evaluation()),
                    $assignment2 = (new Assignment())->setUser($user2)->setStage(new Evaluation()),
                ], // assignments to insert
                [
                    $user1->addUserAssignment($assignment1),
                    $user2->addUserAssignment($assignment2),
                ], // users
                true, // should be returned
            ],
            "Negative 1 - Test that assignments with certain user and active are not being returned by getAssessmentsGroupedByUsers" => [
                [
                    $assignment3 = (new Assignment())->setUser($user3)->setStage((new Evaluation())->setCompletedAt(new \DateTime('yesterday'))),
                    $assignment4 = (new Assignment())->setUser($user4)->setStage((new Evaluation())->setCompletedAt(new \DateTime('yesterday'))),
                ], // assignments to insert
                [
                    $user3->addUserAssignment($assignment3),
                    $user4->addUserAssignment($assignment4),
                ], // users
                false, // should be returned
            ],
            "Negative 2 - Test that assignments with another user and not active are not being returned by getAssessmentsGroupedByUsers" => [
                [
                    (new Assignment())->setUser(new User())->setStage(new Evaluation()),
                    (new Assignment())->setUser(new User())->setStage(new Evaluation()),
                ], // assignments to insert
                [
                    (new User())->addUserAssignment(new Assignment()),
                    (new User())->addUserAssignment(new Assignment()),
                ], // users
                false, // should be returned
            ],
        ];
    }
}