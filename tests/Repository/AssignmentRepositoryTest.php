<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\AssessmentStatus;
use App\Repository\AssignmentRepository;
use App\Repository\UserRepository;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\EntityManagerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssignmentRepositoryTest extends AbstractKernelTestCase
{
    private AssignmentRepository $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentRepository = $this->getContainer()->get(AssignmentRepository::class);
    }

    /**
     * @dataProvider findAllForMultipleStagesProvider
     */
    public function testFindAllForMultipleStages(array $assignments, array $stages, array $expectedAssignments)
    {
        foreach ($assignments as $assignment) {
            $this->entityManager->persist($assignment);
        }
        foreach ($stages as $stage) {
            $this->entityManager->persist($stage);
        }
        $this->entityManager->flush();
        $assignmentsFromDb = $this->assignmentRepository->findAllForMultipleStages($stages);
        self::assertSameSize($assignmentsFromDb, $expectedAssignments);
        foreach ($assignmentsFromDb as $assignment) {
            self::assertContains($assignment, $expectedAssignments);
        }
    }

    public function findAllForMultipleStagesProvider(): array
    {
        return [
            "Positive 1: 1 stage, 2 assignments" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage($stage = new Stage()),
                    ($assignment2 = new Assignment())->setStage($stage),
                ],
                "stages" => [$stage],
                "expectedAssignments" => [
                    $assignment,
                    $assignment2,
                ],
            ],
            "Positive 2: 2 stages, 1 assignment each" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage($stage = new Stage()),
                    ($assignment2 = new Assignment())->setStage($stage2 = new Stage()),
                ],
                "stages" => [$stage, $stage2],
                "expectedAssignments" => [
                    $assignment,
                    $assignment2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider findActiveForUsersProvider
     */
    public function testFindActiveForUsers(array $assignments, array $users, array $expectedAssignments)
    {
        foreach ($assignments as $assignment) {
            $this->entityManager->persist($assignment);
        }
        foreach ($users as $user) {
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
        $assignmentsFromDb = $this->assignmentRepository->findActiveForUsers($users);
        self::assertSameSize($assignmentsFromDb, $expectedAssignments);
        foreach ($assignmentsFromDb as $assignment) {
            self::assertContains($assignment, $expectedAssignments);
        }
    }

    public function findActiveForUsersProvider(): array
    {
        return [
            "Positive 1: 1 user, 2 active assignments, 1 non-active assignment, 1 assignment for a different user" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage($stage = new Stage())->setUser($user = new User()),
                    ($assignment2 = new Assignment())->setStage($stage)->setUser($user),
                    ($assignment3 = new Assignment())->setStage((new Stage())->setCompletedAt(new \DateTime()))->setUser($user),
                    ($assignment4 = new Assignment())->setStage($stage)->setUser(new User()),
                ],
                "users" => [$user],
                "expectedAssignments" => [
                    $assignment,
                    $assignment2,
                ],
            ],
            "Positive 1: 2 users, 1 active assignment each, 1 non-active assignment each" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage(new Stage())->setUser($user = new User()),
                    ($assignment2 = new Assignment())->setStage((new Stage())->setCompletedAt(new \DateTime()))->setUser($user)->setCompletedAt(new \DateTime()),
                    ($assignment3 = new Assignment())->setStage(new Stage())->setUser($user2 = new User()),
                    ($assignment4 = new Assignment())->setStage((new Stage())->setCompletedAt(new \DateTime()))->setUser($user2)->setCompletedAt(new \DateTime()),
                ],
                "users" => [$user, $user2],
                "expectedAssignments" => [
                    $assignment,
                    $assignment3,
                ],
            ],
        ];
    }

    /**
     * @dataProvider findByStageProvider
     */
    public function testFindByStage(array $assignments, Stage $stage, ?Assignment $expectedAssignment)
    {
        foreach ($assignments as $assignment) {
            $this->entityManager->persist($assignment);
        }
        foreach ($stage as $stage) {
            $this->entityManager->persist($stage);
        }
        $this->entityManager->flush();
        $assignmentFromDb = $this->assignmentRepository->findByStage($stage);
        self::assertEquals($assignmentFromDb, $expectedAssignment);
    }

    public function findByStageProvider(): array
    {
        return [
            "Positive 1: stage has 1 assignment" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage($stage = new Stage())->setUser($user = new User()),
                    (new Assignment())->setStage((new Stage())->setCompletedAt(new \DateTime()))->setUser($user),
                ],
                "stage" => $stage,
                "expectedAssignment" => $assignment,
            ],
            "Positive 1: stage has 0 assignments" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage($stage = new Stage())->setUser($user = new User()),
                    (new Assignment())->setStage((new Stage())->setCompletedAt(new \DateTime()))->setUser($user),
                ],
                "stage" => (new Stage())->setId(1),
                "expectedAssignment" => null,
            ],
        ];
    }

    /**
     * @dataProvider findActiveForProjectAndUserProvider
     */
    public function testFindActiveForProjectAndUser(array $assignments, Project $project, User $user, array $expectedAssignments)
    {
        foreach ($assignments as $assignment) {
            $this->entityManager->persist($assignment);
        }
        $this->entityManager->persist($project);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $assignmentsFromDb = $this->assignmentRepository->findActiveForProjectAndUser($project, $user);
        self::assertSameSize($assignmentsFromDb, $expectedAssignments);
        foreach ($assignmentsFromDb as $assignment) {
            self::assertContains($assignment, $expectedAssignments);
        }
    }

    public function findActiveForProjectAndUserProvider(): array
    {
        return [
            "Positive 1: 1 user, 4 assignments for project, but 1 is archived and 1 stage is completed" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(null)
                            ->setAssessmentStream(
                                (new AssessmentStream())->setStatus(AssessmentStatus::NEW)->setAssessment(
                                    ($assessment = new Assessment())
                                        ->setProject(($project = new Project())->setAssessment($assessment))
                                )
                            )
                    )->setUser($user = new User()),
                    ($assignment2 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(null)
                            ->setAssessmentStream(
                                (new AssessmentStream())->setStatus(AssessmentStatus::NEW)->setAssessment(
                                    ($assessment)
                                        ->setProject($project)
                                )
                            )
                    )->setUser($user),
                    ($assignment3 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(null)
                            ->setAssessmentStream(
                                (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)->setAssessment(
                                    ($assessment)
                                        ->setProject($project)
                                )
                            )
                    )->setUser($user),
                    ($assignment4 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(new \DateTime())
                            ->setAssessmentStream(
                                (new AssessmentStream())->setStatus(AssessmentStatus::COMPLETE)->setAssessment(
                                    ($assessment)
                                        ->setProject($project)
                                )
                            )
                    )->setUser($user),
                ],
                "project" => $project,
                "user" => $user,
                "expectedAssignments" => [
                    $assignment,
                    $assignment2,
                ],
            ],
            "Positive 2: 1 user, 2 assignments, 1 is archived, 1 stage is complete" => [
                "assignments" => [
                    ($assignment3 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(null)
                            ->setAssessmentStream(
                                (new AssessmentStream())->setStatus(AssessmentStatus::ARCHIVED)->setAssessment(
                                    ($assessment)
                                        ->setProject($project)
                                )
                            )
                    )->setUser($user),
                    ($assignment4 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(new \DateTime())
                            ->setAssessmentStream(
                                (new AssessmentStream())->setStatus(AssessmentStatus::COMPLETE)->setAssessment(
                                    ($assessment)
                                        ->setProject($project)
                                )
                            )
                    )->setUser($user),
                ],
                "project" => $project,
                "user" => $user,
                "expectedAssignments" => [],
            ],
        ];
    }


    /**
     * @dataProvider findActiveForAssessmentStreamsProvider
     */
    public function testFindActiveForAssessmentStreams(array $assignments, array $assessmentStreams, array $expectedAssignments)
    {
        foreach ($assignments as $assignment) {
            $this->entityManager->persist($assignment);
        }
        foreach ($assessmentStreams as $assessmentStream) {
            $this->entityManager->persist($assessmentStream);
        }
        $this->entityManager->flush();
        $assignmentsFromDb = $this->assignmentRepository->findActiveForAssessmentStreams($assessmentStreams);
        self::assertSameSize($assignmentsFromDb, $expectedAssignments);
        foreach ($assignmentsFromDb as $assignment) {
            self::assertContains($assignment, $expectedAssignments);
        }
    }

    public function findActiveForAssessmentStreamsProvider(): array
    {
        return [
            "Positive 1: 3 assignments one for a completed stage, expecting 2 active ones" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(null)
                            ->setAssessmentStream(($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::NEW))
                    ),
                    ($assignment2 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(null)
                            ->setAssessmentStream(($assessmentStream2 = new AssessmentStream())->setStatus(AssessmentStatus::NEW))
                    ),
                    ($assignment3 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(new \DateTime())
                            ->setAssessmentStream(($assessmentStream3 = new AssessmentStream())->setStatus(AssessmentStatus::NEW))
                    ),
                ],
                "assessmentStreams" => [$assessmentStream, $assessmentStream2, $assessmentStream3],
                "expectedAssignments" => [
                    $assignment,
                    $assignment2,
                ],
            ],
            "Positive 2: 3 assignments, all with completed stages, expecting empty set" => [
                "assignments" => [
                    ($assignment = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(new \DateTime())
                            ->setAssessmentStream(($assessmentStream = new AssessmentStream())->setStatus(AssessmentStatus::NEW))
                    ),
                    ($assignment2 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(new \DateTime())
                            ->setAssessmentStream(($assessmentStream2 = new AssessmentStream())->setStatus(AssessmentStatus::NEW))
                    ),
                    ($assignment3 = new Assignment())->setStage(
                        (new Stage())->setCompletedAt(new \DateTime())
                            ->setAssessmentStream(($assessmentStream3 = new AssessmentStream())->setStatus(AssessmentStatus::NEW))
                    ),
                ],
                "assessmentStreams" => [$assessmentStream, $assessmentStream2, $assessmentStream3],
                "expectedAssignments" => [],
            ],
        ];
    }

}