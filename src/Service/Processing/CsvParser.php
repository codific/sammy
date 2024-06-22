<?php

declare(strict_types=1);

namespace App\Service\Processing;

use Psr\Log\LoggerInterface;

class CsvParser
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getRawArrayFromCsv($csvFilePath): array
    {
        try {
            return $this->csvToArray($csvFilePath);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace()[0]);

            return [];
        }
    }

    /**
     * @throws \Exception
     */
    private function csvToArray($csvFile): array
    {
        $fileArray = [];
        if (!file_exists($csvFile)) {
            throw new \Exception('File not found.');
        }
        $filePointer = fopen($csvFile, 'r');
        if ($filePointer === false) {
            throw new \Exception('File open failed.');
        }
        while (!feof($filePointer)) {
            $fileArray[] = fgetcsv($filePointer, 1000, ',');
        }

        fclose($filePointer);

        return $fileArray;
    }
}
