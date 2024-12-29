<?php

declare(strict_types=1);

namespace App\Service\Processing;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Project;
use App\Service\AssessmentAnswersService;
use App\Service\AssessmentService;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AssessmentExporterService.
 */
class AssessmentExporterService extends ExcelExporter
{

    /**
     * @throws Exception
     */
    public function __construct(
        KernelInterface $httpKernel,
        Filesystem $fileSystem,
        private readonly AssessmentService $assessmentService,
        private readonly AssessmentAnswersService $assessmentAnswersService
    ) {
        parent::__construct($httpKernel, $fileSystem);
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getToolbox(Assessment $assessment, ?Project $project = null): string
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $this->spreadsheetObject = $reader->load($this->httpKernel->getProjectDir().'/public/front/Toolbox/Toolbox.xlsx');

        $this->spreadsheetObject->getProperties()
            ->setCreator('Codific Sammy')
            ->setTitle('Sammy Export')
            ->setSubject('Sammy Export')
            ->setDescription('Sammy export to SAMM Toolbox');

        $this->spreadsheetObject->setActiveSheetIndex(1);

        if ($project === null) {
            $this->spreadsheetObject->getActiveSheet()->setCellValue('D11', 'YOUR PROJECT NAME');
            $this->spreadsheetObject->getActiveSheet()->setCellValue('D10', 'YOUR ORGANIZATION NAME');
        } else {
            $this->spreadsheetObject->getActiveSheet()->setCellValue('D11', $project->getName());
            $this->spreadsheetObject->getActiveSheet()->setCellValue('D10', "Organization");
            $this->spreadsheetObject->getActiveSheet()->setCellValue('D12', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($project->getAssessment()->getLastUpdate()));
        }

        $this->populateAnswers($assessment);

        return $this->saveExcelFile();
    }

    private function populateAnswers(Assessment $assessment): void
    {
        $assessmentStreams = $this->assessmentService->getActiveStreams($assessment, true);
        $allAnswers = $this->assessmentAnswersService->getLatestAnswersByAssessmentStreams($assessmentStreams);


        $answersArray = $this->populateStructuredAnswerArray($this->getStructuredAnswerArray(), $allAnswers);

        $this->populateSheetWithAnswers($answersArray);
    }


    /**
     * answers must be stored in the format given by getStructuredAnswerArray().
     */
    private function populateSheetWithAnswers(array $structuredAnswersArray, int $startRow = 18, string $answerColumn = 'F'): void
    {
        $cellRow = $startRow; // starting answer row in Toolbox.xlsx (default 18)
        $cellColumn = $answerColumn; // answer column in Toolbox.xlsx (default 'F')
        $skipper = 0; // used to skip the non-answer rows (one every 3 answers and one every 18 answers)
        foreach ($structuredAnswersArray as $answer) {
            $this->spreadsheetObject->getActiveSheet()->setCellValue("$cellColumn$cellRow", $answer?->getAnswer()->getText());
            $cellRow += 2;
            ++$skipper;
            if ($skipper % 3 === 0) {
                ++$cellRow;
            }
            if ($skipper === 18) {
                $skipper = 0;
                ++$cellRow;
            }
        }
    }

    private function populateStructuredAnswerArray(array $structuredAnswersArray, array $assessmentAnswers): array
    {
        foreach ($assessmentAnswers as $answer) {
            $answerValue =
                $answer->getAssessmentStream()->getStream()->getPractice()->getBusinessFunction()->getOrder() * 1000 +
                $answer->getAssessmentStream()->getStream()->getPractice()->getOrder() * 100 +
                $answer->getAssessmentStream()->getStream()->getOrder() * 10 +
                $answer->getQuestion()->getActivity()->getPracticeLevel()->getMaturityLevel()->getLevel();

            $structuredAnswersArray[$answerValue] = $answer;
        }

        return $structuredAnswersArray;
    }

    /**
     * Returns a structured array for storing answers in a tidy way.
     */
    private function getStructuredAnswerArray(): array
    {
        $answersArray = [];
        for ($businessFunctionOrder = 1; $businessFunctionOrder <= 5; ++$businessFunctionOrder) {
            for ($practiceOrder = 1; $practiceOrder <= 3; ++$practiceOrder) {
                for ($streamOrder = 1; $streamOrder <= 2; ++$streamOrder) {
                    for ($level = 1; $level <= 3; ++$level) {
                        $answersArray[$level + 10 * $streamOrder + 100 * $practiceOrder + 1000 * $businessFunctionOrder] = null;
                    }
                }
            }
        }

        return $answersArray;
    }
}
