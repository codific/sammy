<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\AssessmentStream;
use App\Entity\User;
use App\Enum\AssessmentStatus;
use App\Enum\Role;
use App\Repository\AssessmentAnswerRepository;
use App\Repository\AssessmentStreamRepository;
use App\Repository\ProjectRepository;
use App\Service\MetamodelService;
use App\Service\Processing\SammToolboxImporterService;
use App\Tests\_support\AbstractKernelTestCase;
use App\Tests\builders\UserBuilder;
use App\Utils\Constants;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class SammToolboxImporterServiceTest extends AbstractKernelTestCase
{

    private SammToolboxImporterService $toolboxImporterService;

    public function setUp(): void
    {
        parent::setUp();
        $this->toolboxImporterService = self::getContainer()->get(SammToolboxImporterService::class);
    }
    /**
     * @dataProvider testToolboxImportDataProvider
     */
    public function testToolboxImport(
        User $user,
        string $file,
        bool $autoValidate,
        AssessmentStatus $expectedStreamStatus,
        int $expectedSavedAnswersForFirstStream,
        int $expectedMetamodelId,
        string $streamNameWithAnswers
    ): void {
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $project = $this->toolboxImporterService->import(new UploadedFile($file, "toolbox"), $autoValidate, $user);

        $projectRepository = self::getContainer()->get(ProjectRepository::class);
        $project = $projectRepository->find($project->getId());

        self::assertEquals($expectedMetamodelId, $project->getMetamodel()->getId());

        $metamodelService = self::getContainer()->get(MetamodelService::class);

        $streams = $metamodelService->getStreams($project->getMetamodel());
        $neededStream = null;
        foreach ($streams as $stream) {
            if ($stream->getName() === $streamNameWithAnswers) {
                $neededStream = $stream;
            }
        }

        $assessmentStreamRepository = self::getContainer()->get(AssessmentStreamRepository::class);

        /** @var AssessmentStream $assessmentStream */
        $assessmentStream = $assessmentStreamRepository->findOneActiveByAssessmentAndStream($project->getAssessment(), $neededStream);
        $assessmentAnswerRepository = self::getContainer()->get(AssessmentAnswerRepository::class);
        $answers = $assessmentAnswerRepository->findBy(["stage" => $assessmentStream->getLastEvaluationStage()]);

        self::assertEquals($expectedStreamStatus, $assessmentStream->getStatus());
        self::assertEquals($expectedSavedAnswersForFirstStream, sizeof($answers));
    }

    private function testToolboxImportDataProvider(): array
    {
        $user = (new UserBuilder())->withRoles([Role::USER->string(), Role::MANAGER->string()])->build();

        $toolboxFolder = "tests/files_for_tests";

        return [
            "Positive test 1, auto validate toolbox 2.0, expect first stream to be populated and validated " => [
                $user, // user
                $toolboxFolder . "/" . "toolbox-2.0v.xlsx", // file
                true, // auto validate
                AssessmentStatus::VALIDATED, // expected stream status
                3, // expected saved answers for first stream
                Constants::SAMM_ID, // expected metamodel
                "Create and Promote", // stream name with answers
            ],
            "Positive test 2, don't validate toolbox 2.0, expect first stream to be populated and submitted " => [
                $user, // user
                $toolboxFolder . "/" . "toolbox-2.0v.xlsx", // file
                false, // auto validate
                AssessmentStatus::IN_VALIDATION, // expected stream status
                3, // expected saved answers for first stream
                Constants::SAMM_ID, // expected metamodel
                "Create and Promote", // stream name with answers
            ],
        ];
    }
}