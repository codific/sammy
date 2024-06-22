<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Question;
use App\Service\QuestionnaireService;
use App\Tests\_support\AbstractKernelTestCase;

class QuestionnaireServiceTest extends AbstractKernelTestCase
{
    private QuestionnaireService $questionnaireService;

    public function setUp(): void
    {
        parent::setUp();
        $this->questionnaireService = self::getContainer()->get(QuestionnaireService::class);
    }

    public function testIsStreamCompleted()
    {
        /** @var Question $sammQuestion1 */
        $sammQuestion1 = $this->getObjectOfClass(Question::class, 1);
        self::assertFalse($this->questionnaireService->isStreamCompleted($sammQuestion1->getActivity()->getStream(), []));
        self::assertFalse($this->questionnaireService->isStreamCompleted($sammQuestion1->getActivity()->getStream(), [2 => 2, 4 => 4]));
        self::assertTrue($this->questionnaireService->isStreamCompleted($sammQuestion1->getActivity()->getStream(), [2 => 2, 4 => 4, 6 => 6]));
    }
}