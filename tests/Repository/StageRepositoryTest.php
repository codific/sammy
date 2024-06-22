<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Improvement;
use App\Entity\Project;
use App\Entity\Remark;
use App\Entity\Stage;
use App\Entity\Stream;
use App\Entity\User;
use App\Entity\Validation;
use App\Repository\ProjectRepository;
use App\Repository\RemarkRepository;
use App\Repository\StageRepository;
use App\Repository\UserRepository;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\EntityManagerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StageRepositoryTest extends AbstractKernelTestCase
{
    private StageRepository $stageRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stageRepository = self::getContainer()->get(StageRepository::class);
    }

    /**
     * @dataProvider getStreamCompletedStagesProvider
     */
    public function testGetStreamCompletedStages(array $stages, AssessmentStream $assessmentStream, ?int $maxResults, array $expectedStages)
    {
        foreach ($stages as $stage) {
            $this->entityManager->persist($stage);
        }
        $this->entityManager->flush();
        $stagesFromDb = $this->stageRepository->getStreamCompletedStages($assessmentStream, $maxResults);
        self::assertSameSize($stagesFromDb, $expectedStages);
        foreach ($stagesFromDb as $stage) {
            self::assertContains($stage, $expectedStages);
        }
    }

    public function getStreamCompletedStagesProvider(): array
    {
        return [
            "Positive 1: 3 stages, 2 complete, max results is null, expecting 2 stages" => [
                "stages" => [
                    ($stage = new Evaluation())->setCompletedAt(new \DateTime())
                        ->setAssessmentStream(
                            ($assessmentStream = new AssessmentStream())->setAssessment(
                                ($assessment = new Assessment())
                                    ->addAssessmentAssessmentStream($assessmentStream)
                            )->setStream(new Stream())->addAssessmentStreamStage($stage)
                        ),
                    ($stage2 = new Validation())->setCompletedAt(new \DateTime())
                        ->setAssessmentStream(($assessmentStream)->addAssessmentStreamStage($stage2)),
                    ($stage3 = new Evaluation())->setAssessmentStream(($assessmentStream)->addAssessmentStreamStage($stage3)),
                ],
                "assessment stream" => $assessmentStream,
                null,
                "expectedRemarks" => [$stage, $stage2],
            ],
            "Positive 2: 3 stages, 3 complete, max results is 2, expecting 2 stages" => [
                "stages" => [
                    ($stage = new Evaluation())->setCompletedAt(new \DateTime("+2 day"))
                        ->setAssessmentStream(
                            ($assessmentStream = new AssessmentStream())->setAssessment(
                                ($assessment = new Assessment())
                                    ->addAssessmentAssessmentStream($assessmentStream)
                            )->setStream(new Stream())->addAssessmentStreamStage($stage)
                        ),
                    ($stage2 = new Validation())->setCompletedAt(new \DateTime("+10 day"))
                        ->setAssessmentStream(($assessmentStream)->addAssessmentStreamStage($stage2)),
                    ($stage3 = new Evaluation())->setCompletedAt(new \DateTime("+5 day"))
                        ->setAssessmentStream(($assessmentStream)->addAssessmentStreamStage($stage3)),
                ],
                "assessment stream" => $assessmentStream,
                2,
                "expectedRemarks" => [$stage2, $stage3],
            ],
            "Positive 3: 3 stages, 3 complete, max results is negative, expecting 3 stages" => [
                "stages" => [
                    ($stage = new Evaluation())->setCompletedAt(new \DateTime("+2 day"))
                        ->setAssessmentStream(
                            ($assessmentStream = new AssessmentStream())->setAssessment(
                                ($assessment = new Assessment())
                                    ->addAssessmentAssessmentStream($assessmentStream)
                            )->setStream(new Stream())->addAssessmentStreamStage($stage)
                        ),
                    ($stage2 = new Evaluation())->setCompletedAt(new \DateTime("+10 day"))
                        ->setAssessmentStream(($assessmentStream)->addAssessmentStreamStage($stage2)),
                    ($stage3 = new Improvement())->setCompletedAt(new \DateTime("+5 day"))
                        ->setAssessmentStream(($assessmentStream)->addAssessmentStreamStage($stage3)),
                ],
                "assessment stream" => $assessmentStream,
                -10,
                "expectedRemarks" => [$stage, $stage2, $stage3],
            ],
            "Positive 4: 3 stages, 3 different streams, same assessment and same stream" => [
                "stages" => [
                    ($stage = new Evaluation())->setCompletedAt(new \DateTime("+2 day"))
                        ->setAssessmentStream(
                            ($assessmentStream = new AssessmentStream())->setAssessment(
                                ($assessment = new Assessment())
                                    ->addAssessmentAssessmentStream($assessmentStream)
                            )->setStream($stream = new Stream())->addAssessmentStreamStage($stage)
                        ),
                    ($stage2 = new Improvement())->setCompletedAt(new \DateTime("+10 day"))
                        ->setAssessmentStream(
                            ($assessmentStream2 = new AssessmentStream())->setAssessment(
                                ($assessment)
                                    ->addAssessmentAssessmentStream($assessmentStream2)
                            )->setStream($stream)->addAssessmentStreamStage($stage2)
                        ),
                    ($stage3 = new Improvement())->setCompletedAt(new \DateTime("+10 day"))
                        ->setAssessmentStream(
                            ($assessmentStream3 = new AssessmentStream())->setAssessment(
                                ($assessment)
                                    ->addAssessmentAssessmentStream($assessmentStream3)
                            )->setStream($stream)->addAssessmentStreamStage($stage3)
                        ),
                ],
                "assessment stream" => $assessmentStream,
                null,
                "expectedRemarks" => [$stage, $stage2, $stage3],
            ],
        ];
    }

    /**
     * @dataProvider findByAssessmentStreamsProvider
     */
    public function testFindByAssessmentStreams(array $stages, array $assessmentStreams, string $type, array $expectedStages)
    {
        foreach ($stages as $stage) {
            $this->entityManager->persist($stage);
        }
        $this->entityManager->flush();
        $stagesFromDb = $this->stageRepository->findByAssessmentStreams($assessmentStreams, $type);
        self::assertSameSize($stagesFromDb, $expectedStages);
        foreach ($stagesFromDb as $stage) {
            self::assertContains($stage, $expectedStages);
        }
    }

    public function findByAssessmentStreamsProvider(): array
    {
        return [
            "Positive 1: 3 evaluations, 1 validation, 2 assessment streams, expecting 3 evaluation type streams" => [
                "stages" => [
                    ($stage = new Evaluation())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream = new AssessmentStream())),
                    ($stage2 = new Validation())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream)),
                    ($stage3 = new Evaluation())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream2 = new AssessmentStream())),
                    ($stage4 = new Evaluation())->setAssessmentStream(($assessmentStream2)),
                ],
                "assessment streams" => [$assessmentStream, $assessmentStream2],
                Evaluation::class,
                "expectedRemarks" => [$stage, $stage3, $stage4],
            ],
            "Positive 2: 1 improvement, 1 validation, 2 evaluations, 4 assessment streams, expecting 1 improvement" => [
                "stages" => [
                    ($stage = new Improvement())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream = new AssessmentStream())),
                    ($stage2 = new Validation())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream2 = new AssessmentStream())),
                    ($stage3 = new Evaluation())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream3 = new AssessmentStream())),
                    ($stage4 = new Evaluation())->setCompletedAt(new \DateTime())->setAssessmentStream(($assessmentStream4 = new AssessmentStream())),
                ],
                "assessment streams" => [$assessmentStream, $assessmentStream2, $assessmentStream3, $assessmentStream4],
                Improvement::class,
                "expectedRemarks" => [$stage],
            ],
        ];
    }


}