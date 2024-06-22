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

namespace App\Service\Processing;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;

class ExcelExporter
{
    protected Session $exportCache;
    protected Spreadsheet $spreadsheetObject;
    private const MAX_CELL_WIDTH = 85;

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct(
        protected readonly KernelInterface $httpKernel,
        protected readonly Filesystem $fileSystem
    ) {
        $this->spreadsheetObject = new Spreadsheet();
        $this->spreadsheetObject->getProperties()
            ->setCreator('Codific Excel Exporter Plugin')
            ->setTitle('Export')
            ->setSubject('Export')
            ->setDescription('Excel Autoexport');
        $this->spreadsheetObject->removeSheetByIndex(0);

        $this->exportCache = new Session();
        $this->exportCache->set('sheetIndex', 0);
        $this->exportCache->set('greyStyle', 'BFBFBF');
    }

    /**
     * Saves the Excel file in the file system and returns its name. If needed, it will create the needed directories from the second parameter.
     *
     * @param string $name Optional name if not provided random string is generated
     *
     * @throws Exception
     */
    protected function saveExcelFile(string $name = ''): string
    {
        $objWriter = new Xlsx($this->spreadsheetObject);
        $objWriter->setIncludeCharts(true);

        $path = $this->httpKernel->getProjectDir().'/private/userfiles/exports';
        if (!$this->fileSystem->exists($path)) {
            $this->fileSystem->mkdir($path);
        }

        if ($name === '') {
            $rand = uniqid();
            $name = hash('sha256', $rand);
        }
        $filename = $name.'.xlsx';
        $filename = str_replace(' ', '_', $filename);
        $objWriter->save($path.'/'.$filename);

        return $path.'/'.$filename;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function getNewSheet($name = null): Worksheet
    {
        $this->spreadsheetObject->createSheet();
        $sheetIndex = $this->exportCache->get('sheetIndex');
        $this->exportCache->set('sheetIndex', $this->exportCache->get('sheetIndex') + 1);

        $this->spreadsheetObject->setActiveSheetIndex($sheetIndex);

        if ($name !== null) {
            $this->spreadsheetObject->getActiveSheet()->setTitle($name);
        }

        return $this->spreadsheetObject->getActiveSheet();
    }

    protected function setColumnAutoWidth($worksheet, $columnName): void
    {
        $worksheet->getColumnDimension($columnName)->setAutoSize(true);
    }

    /**
     * This will break if you try to change columns grater than Z (column after Z is AA, etc.).
     */
    protected function setNColumnsAutoWidth(Worksheet $worksheet, int $numberOfColumns, string $startingColumnName): void
    {
        for ($i = 0; $i < $numberOfColumns; ++$i) {
            $this->setColumnAutoWidth($worksheet, $startingColumnName++);
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function printHeader(array $headers, Worksheet $sheet, int $col, int $row): void
    {
        foreach ($headers as $cell) {
            $sheet->setCellValue([$col++, $row], $cell);
        }

        $cellIterator = $sheet->getRowIterator($row, $row)->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        /* @var $cell Cell */
        foreach ($cellIterator as $cell) {
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
            $sheet->getStyle($cell->getCoordinate())->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'BFBFBF']]]);
            $sheet->getStyle($cell->getCoordinate())->getFont()->setBold(true);
            $sheet->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);
        }
    }

    protected function printData(array $data, Worksheet $sheet, int $col, int $row): void
    {
        foreach ($data as $dataRow) {
            $this->printParticipantData($dataRow, $sheet, $col, $row);
            $col = 1;
            ++$row;
        }
    }

    protected function printParticipantData(array $data, Worksheet $sheet, int $col, int $row): void
    {
        foreach ($data as $cell) {
            $sheet->setCellValue([$col++, $row], $cell);
        }
    }

    protected function readonlyStyle(Worksheet $sheet, Cell $cell): void
    {
        $sheet->getStyle($cell->getCoordinate())->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'BFBFBF']]]);
    }

    protected function autosize(Worksheet $sheet): void
    {
        $sheet->calculateColumnWidths();
        $cellIterator = $sheet->getRowIterator(4, 4)->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        /* @var $cell Cell */
        foreach ($cellIterator as $cell) {
            if ($sheet->getColumnDimension($cell->getColumn())->getWidth() > self::MAX_CELL_WIDTH) {
                $sheet->getColumnDimension($cell->getColumn())->setAutoSize(false);
                $sheet->getColumnDimension($cell->getColumn())->setWidth(self::MAX_CELL_WIDTH);
            }
        }
    }

    protected function createDropdownValidator(Worksheet $sheet, int $col, int $row, array $allowedValues): void
    {
        $validator = $sheet->getCell([$col, $row])->getDataValidation();
        $validator->setType(DataValidation::TYPE_LIST);
        $validator->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validator->setAllowBlank(false);
        $validator->setShowInputMessage(true);
        $validator->setShowDropDown(true);
        $validator->setPromptTitle('Pick from list');
        $validator->setPrompt('Please pick a value from the drop-down list.');
        $validator->setErrorTitle('Input error');
        $validator->setError('Value is not in list');
        $formula = str_replace(['"', '_'], ['', ' '], implode('","', $allowedValues));
        $validator->setFormula1('"'.$formula.'"');
    }

    protected function createBoolValidator(Worksheet $sheet, int $col, int $row): void
    {
        $this->createDropdownValidator($sheet, $col, $row, ['TRUE', 'FALSE']);
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(string $title, array $headers, array $data, string $fileName = ''): string
    {
        $sheet = $this->getNewSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $row = 1;
        $this->printHeader($headers, $sheet, 1, $row++);
        $this->printData($data, $sheet, 1, $row);

        return $this->saveExcelFile($fileName);
    }
}
