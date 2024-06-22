<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Entity\Improvement;
use App\Entity\Stage;
use App\Entity\Validation;
use App\Enum\ImprovementStatus;
use App\Enum\ValidationStatus;
use App\Repository\AssignmentRepository;
use App\Repository\StageRepository;
use App\ViewStructures\Timeline\EventIcon;
use App\ViewStructures\Timeline\TimelineEvent;
use Psr\Log\LoggerInterface;

class StagesTimelineService
{
    private readonly array $EVENT_ICONS;

    public function __construct(
        private readonly StageRepository $stageRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly LoggerInterface $logger
    ) {
        $this->EVENT_ICONS = [
            'SUBMITTED' => new EventIcon('fa-list-ol', 'text-primary'),
            'ACCEPTED' => new EventIcon('fa-certificate', 'text-success'),
            'AUTO_ACCEPTED' => new EventIcon('fa-check-double', 'text-success'),
            'REJECTED' => new EventIcon('fa-certificate', 'text-danger'),
            'RETRACTED' => new EventIcon('fa-user-times', 'text-danger'),
            'IMPROVEMENT' => new EventIcon('fa-arrow-alt-circle-up', 'text-success'),
            'COMPLETED' => new EventIcon('fa-dot-circle', 'text-alternate'),
            'RESTART' => new EventIcon('fa-step-forward', 'text-warning'),
        ];
    }

    public function getTimelineEvents(AssessmentStream $assessmentStream): array
    {
        $stages = $this->getEventStages($assessmentStream);

        $assignments = $this->getAssignmentsByStages($stages);

        return $this->constructTimelineEventsArray($stages, $assignments);
    }

    /**
     * @param Stage[] $stages
     *
     * @return TimelineEvent[]
     */
    private function constructTimelineEventsArray(array $stages, array $assignments): array
    {
        $timelineEvents = [];
        $assessmentStreamId = null;
        foreach ($stages as $stage) {
            $assignment = $assignments[$stage->getId()] ?? null;
            $newline = ($assessmentStreamId !== null && $stage->getAssessmentStream()->getId() !== $assessmentStreamId);

            $constructEvent = function (EventIcon $icon, string $action) use ($stage, $assignment, $newline) {
                return new TimelineEvent(
                    $icon,
                    $stage->getEntityName(),
                    $stage->getCompletedAt(),
                    $stage->getSubmittedBy(),
                    $action,
                    $assignment?->getUser(),
                    $newline,
                );
            };

            $constructEvaluationEvent = fn () => $constructEvent($this->EVENT_ICONS['SUBMITTED'], 'submitted_by');

            $constructValidationEvent = function (Stage $validation) use ($constructEvent) {
                if (!$validation instanceof Validation) {
                    throw new \Exception('Wrong stage type');
                }
                /**
                 * @var EventIcon $icon
                 * @var string    $action
                 */
                [$icon, $action] = match ($validation->getStatus()) {
                    ValidationStatus::ACCEPTED => [$this->EVENT_ICONS['ACCEPTED'], 'validated_by'],
                    ValidationStatus::REJECTED => [$this->EVENT_ICONS['REJECTED'], 'rejected_by'],
                    ValidationStatus::AUTO_ACCEPTED => [$this->EVENT_ICONS['AUTO_ACCEPTED'], 'auto_validated'],
                    ValidationStatus::RETRACTED => [$this->EVENT_ICONS['RETRACTED'], 'retracted_by'],
                    ValidationStatus::NEW => throw new \Exception('This should never happen')
                };

                return $constructEvent($icon, $action);
            };

            $constructImprovementEvent = function (Stage $improvement) use ($constructEvent) {
                if (!$improvement instanceof Improvement) {
                    throw new \Exception('Wrong stage type');
                }

                return match ($improvement->getStatus()) {
                    ImprovementStatus::IMPROVE => $constructEvent($this->EVENT_ICONS['IMPROVEMENT'], 'improved_by'),

                    ImprovementStatus::WONT_IMPROVE => $improvement->getCompletedAt() !== null ? $constructEvent($this->EVENT_ICONS['RESTART'], 'restarted_by') :
                        $constructEvent($this->EVENT_ICONS['COMPLETED'], 'completed'),

                    ImprovementStatus::NEW, ImprovementStatus::DRAFT => throw new \Exception('This should never happen'),
                };
            };

            try {
                $event = match (get_class($stage)) {
                    Evaluation::class => $constructEvaluationEvent(),
                    Validation::class => $constructValidationEvent($stage),
                    Improvement::class => $constructImprovementEvent($stage),
                    default => throw new \Exception('This should never happen')
                };

                $assessmentStreamId = $stage->getAssessmentStream()->getId();
                $timelineEvents[] = $event;
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), $exception->getTrace()[0]);
            }
        }

        return $timelineEvents;
    }

    /**
     * @param Stage[] $stages
     *
     * @return Stage[]
     */
    private function sortStagesByCompletedAndClass(array $stages): array
    {
        $getClassOrder = fn ($stage) => match (get_class($stage)) {
            Evaluation::class => 1,
            Validation::class => 2,
            Improvement::class => 3,
            default => 0
        };
        $getTimestamp = fn (Stage $stage) => $stage->getCompletedAt()?->getTimestamp() ?? $stage->getUpdatedAt()->getTimestamp();

        usort(
            $stages,
            fn (Stage $stageA, Stage $stageB) => $getTimestamp($stageB) <=> $getTimestamp($stageA) ?: /* @phpstan-ignore-line */
                $getClassOrder($stageB) <=> $getClassOrder($stageA) // big to small
        );

        return $stages;
    }

    private function getEventStages(AssessmentStream $assessmentStream): array
    {
        $stages = $this->stageRepository->getStreamCompletedStages($assessmentStream, 100);
        $last = $assessmentStream->getAssessmentStreamStages()->last();
        $completed = $last instanceof Improvement && $last->getStatus() === ImprovementStatus::WONT_IMPROVE ? [$last] : [];

        $stages = array_merge($stages, $completed);

        return $this->sortStagesByCompletedAndClass($stages);
    }

    private function getAssignmentsByStages(array $stages): array
    {
        $assignments = $this->assignmentRepository->findAllForMultipleStages($stages);

        return array_combine(
            array_map(
                fn (Assignment $assignment) => $assignment->getStage()?->getId(),
                $assignments,
            ),
            $assignments
        );
    }
}
