<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Stream;

class QuestionnaireService
{
    public function __construct(
        private readonly MetamodelService $metamodelService
    ) {
    }

    public function isStreamCompleted(Stream $stream, $savedAnswers): bool
    {
        $streamQuestions = $this->metamodelService->getQuestionsByStream($stream);
        foreach ($streamQuestions as $question) {
            if (!array_key_exists($question->getId(), $savedAnswers)) {
                return false;
            }
        }

        return true;
    }
}
