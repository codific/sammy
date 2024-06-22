<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Group;
use App\Entity\GroupProject;
use App\Entity\GroupUser;
use App\Entity\Project;
use App\Entity\Remark;
use App\Entity\Stage;
use App\Repository\RemarkRepository;
use App\Repository\UserRepository;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\EntityManagerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RemarkRepositoryTest extends AbstractKernelTestCase
{
    private RemarkRepository $remarkRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->remarkRepository = $this->getContainer()->get(RemarkRepository::class);
    }

    /**
     * @dataProvider findAllForMultipleStagesProvider
     */
    public function testFindAllForMultipleStages(array $remarks, array $stages, array $expectedRemarks)
    {
        foreach ($remarks as $remark) {
            $this->entityManager->persist($remark);
        }
        $this->entityManager->flush();
        $remarksFromDb = $this->remarkRepository->findAllForMultipleStages($stages);
        self::assertSameSize($remarksFromDb, $expectedRemarks);
        foreach ($remarksFromDb as $remark) {
            self::assertContains($remark, $expectedRemarks);
        }
    }

    public function findAllForMultipleStagesProvider(): array
    {
        return [
            "Positive 1: 3 remarks in DB, 2 stages with 2 remarks, expecting 2 remarks " => [
                "remarks" => [
                    ($remark = new Remark())->setStage($stage = new Evaluation())->setCreatedAt(new \DateTime("-3 day"))->setDeletedAt(null),
                    ($remark2 = new Remark())->setStage($stage2 = new Evaluation())->setCreatedAt(new \DateTime("-10 day"))->setDeletedAt(new \DateTime()),
                    ($remark3 = new Remark())->setStage($stage3 = new Evaluation()),
                ],
                "stages" => [$stage, $stage2],
                "expectedRemarks" => [$remark, $remark2],
            ],
            "Positive 2: 3 remarks in DB, passing a stage without remarks, expecting empty set" => [
                "remarks" => [
                    ($remark = new Remark())->setStage($stage = new Evaluation()),
                    ($remark2 = new Remark())->setStage($stage2 = new Evaluation()),
                    ($remark3 = new Remark())->setStage($stage3 = new Evaluation()),
                ],
                "stages" => [(new Stage())->setId(123)],
                "expectedRemarks" => [],
            ],
        ];
    }

    /**
     * @dataProvider findByAssessmentStreamsProvider
     */
    public function testFindByAssessmentStreams(array $remarks, array $assessmentStreams, array $expectedRemarks)
    {
        foreach ($remarks as $remark) {
            $this->entityManager->persist($remark);
        }
        $this->entityManager->flush();
        $remarksFromDb = $this->remarkRepository->findByAssessmentStreams($assessmentStreams);
        self::assertSameSize($remarksFromDb, $expectedRemarks);
        foreach ($remarksFromDb as $remark) {
            self::assertContains($remark, $expectedRemarks);
        }
    }

    public function findByAssessmentStreamsProvider(): array
    {
        return [
            "Positive 1: 3 remarks in DB, 2 assessment streams with 1 remark each but one is deleted, expecting 1 remark" => [
                "remarks" => [
                    ($remark = new Remark())->setStage(($stage = new Evaluation())->setAssessmentStream($assessmentStream = new AssessmentStream()))
                        ->setCreatedAt(new \DateTime("-3 day"))->setDeletedAt(null),
                    ($remark2 = new Remark())->setStage(($stage = new Evaluation())->setAssessmentStream($assessmentStream2 = new AssessmentStream()))
                        ->setCreatedAt(new \DateTime("-10 day"))->setDeletedAt(new \DateTime()),
                    ($remark3 = new Remark())->setStage(($stage = new Evaluation())->setAssessmentStream($assessmentStream3 = new AssessmentStream()))
                        ->setCreatedAt(new \DateTime("-10 day")),
                ],
                "assessment streams" => [$assessmentStream, $assessmentStream2],
                "expectedRemarks" => [$remark],
            ],
            "Positive 2: 3 remarks in DB, 3 assessment streams, all remarks are deleted, expecting empty set" => [
                "remarks" => [
                    ($remark = new Remark())->setStage(($stage = new Evaluation())->setAssessmentStream($assessmentStream = new AssessmentStream()))
                        ->setCreatedAt(new \DateTime("-3 day"))->setDeletedAt(new \DateTime()),
                    ($remark2 = new Remark())->setStage(($stage = new Evaluation())->setAssessmentStream($assessmentStream2 = new AssessmentStream()))
                        ->setCreatedAt(new \DateTime("-10 day"))->setDeletedAt(new \DateTime()),
                    ($remark3 = new Remark())->setStage(($stage = new Evaluation())->setAssessmentStream($assessmentStream3 = new AssessmentStream()))
                        ->setCreatedAt(new \DateTime("-10 day"))->setDeletedAt(new \DateTime()),
                ],
                "assessment streams" => [$assessmentStream, $assessmentStream2, $assessmentStream3],
                "expectedRemarks" => [],
            ],
        ];
    }


}