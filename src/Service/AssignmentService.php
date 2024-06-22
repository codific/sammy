<?php
/**
 * This is automatically generated file using the Codific Prototizer.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\Stage;
use App\Entity\User;
use App\Event\Application\Post\PostAddAssignmentEvent;
use App\Event\Application\Pre\PreAddAssignmentEvent;
use App\Exception\QueueNotOnlineException;
use App\Repository\AssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AssignmentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     */
    public function addAssignment(Assignment $assignment, Stage $stage, User $user, User $assignor, ?string $remark = null): void
    {
        $this->eventDispatcher->dispatch(new PreAddAssignmentEvent($stage->getAssessmentStream()));

        $assignment->setStage($stage)
            ->setUser($user)
            ->setAssignedBy($assignor)
            ->setRemark($remark);
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PostAddAssignmentEvent($stage->getAssessmentStream(), $user));
    }

    public function deleteAssignment(Assignment $assignment): void
    {
        $this->assignmentRepository->trash($assignment);
    }

    public function deleteStageAssignments(Stage $stage, ?User $exceptThisUser = null): array
    {
        $users = [];
        $assignments = $this->assignmentRepository->findBy(['stage' => $stage]);
        foreach ($assignments as $assignment) {
            if ($exceptThisUser === null || $assignment->getUser()?->getId() !== $exceptThisUser->getId()) {
                $users[] = $assignment->getUser();
                $this->assignmentRepository->trash($assignment);
            }
        }

        return array_unique($users);
    }

    public function completeStageAssignments(Stage $stage): void
    {
        $assignments = $this->assignmentRepository->findBy(['stage' => $stage, 'completedAt' => null]);
        foreach ($assignments as $assignment) {
            $this->assignmentRepository->complete($assignment);
        }
    }

    public function getStageAssignment(?Stage $stage): ?Assignment
    {
        $result = null;
        if ($stage !== null) {
            try {
                $result = $this->assignmentRepository->findByStage($stage);
            } catch (NonUniqueResultException $e) {
                $this->logger->error($e->getMessage(), $e->getTrace()[0]);
            }
        }

        return $result;
    }

    /**
     * @return Assignment[]
     */
    public function getAssessmentStreamCurrentAssignments(array $assessmentStreams): array
    {
        $assignments = $this->assignmentRepository->findActiveForAssessmentStreams($assessmentStreams);
        $assessmentStreamAssignments = [];
        foreach ($assignments as $assignment) {
            $assessmentStreamAssignments[$assignment->getStage()->getAssessmentStream()->getId()] = $assignment;
        }

        return $assessmentStreamAssignments;
    }

    /**
     * @param User[] $users
     *
     * @return array<Assignment[]>
     */
    public function getAssessmentsGroupedByUsers(array $users): array
    {
        return array_reduce(
            $this->assignmentRepository->findActiveForUsers($users),
            function (array $accumulator, Assignment $assignment) {
                $accumulator[$assignment->getUser()->getId()][] = $assignment;

                return $accumulator;
            },
            []
        );
    }
}
