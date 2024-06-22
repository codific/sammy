<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Assignment;
use App\Entity\Evaluation;
use App\Service\MetamodelService;
use App\Service\StagesTimelineService;
use App\Tests\_support\AbstractKernelTestCase;

class StageTimelineServiceTest extends AbstractKernelTestCase
{
    /**
     * @dataProvider getTimelineEventsProvider
     *
     * @testdox $_dataName
     */
    public function testGetTimelineEvents(AssessmentStream $assessmentStream, Assignment $assignment)
    {
        $this->entityManager->persist($assessmentStream);
        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        /** @var StagesTimelineService $timelineService */
        $timelineService = self::getContainer()->get(StagesTimelineService::class);

        $results = $timelineService->getTimelineEvents($assessmentStream);

        foreach ($results as $result) {
            self::assertEquals(strtolower($assessmentStream->getActiveStageName()), strtolower($result->title));
            self::assertEquals($assessmentStream->getCurrentStage()->getCompletedAt(), $result->completedAt);
        }
    }

    public function getTimelineEventsProvider(): array
    {
        $evaluation = (new Evaluation());
        $stream = self::getContainer()->get(MetamodelService::class)->getStreams()[0];
        $assessmentStream = (new AssessmentStream())->addAssessmentStreamStage($evaluation)->setStream($stream)->setAssessment(new Assessment());
        $evaluation->setAssessmentStream($assessmentStream)->setCompletedAt(new \DateTime('yesterday'));
        $assignment = (new Assignment())->setStage($evaluation);

        return [
            'Test that the timeline events are properly returned by getTimelineEvents' => [
                $assessmentStream,
                $assignment,
            ],
        ];
    }

}
