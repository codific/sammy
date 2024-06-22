<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Assessment;
use App\Entity\AssessmentAnswer;
use App\Entity\AssessmentStream;
use App\Entity\Evaluation;
use App\Entity\Question;
use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class PublicAssessmentService
{
    public function __construct(
        private readonly QuestionRepository $questionRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly MetamodelService $metamodelService,
        private readonly RequestStack $requestStack
    ) {
    }

    public function saveAssessmentAnswers(array $answers): void
    {
        foreach ($answers as $answerObj) {
            $answer = $this->answerRepository->find($answerObj['answerId']);
            $question = $this->questionRepository->find($answerObj['questionId']);
            $this->saveAssessmentAnswer($question, $answer);
        }
    }

    private function saveAssessmentAnswer(Question $question, ?Answer $answer): void
    {
        $assessment = $this->getSessionAssessment();
        $assessmentStream = $this->getAssessmentStream(
            $assessment,
            $question->getActivity()->getStream()->getExternalId()
        );
        $evaluation = $assessmentStream->getLastEvaluationStage();

        $assessmentAnswer = $evaluation?->getStageAssessmentAnswers()?->filter(function (AssessmentAnswer $element) use ($question) {
            return $element->getQuestion()->getId() === $question->getId();
        })->first();
        if ($assessmentAnswer instanceof AssessmentAnswer) {
            $assessmentAnswer->setAnswer($answer);
        } else {
            if ($evaluation === null) {
                $evaluation = (new Evaluation())->setAssessmentStream($assessmentStream);
                $assessmentStream->getAssessmentStreamStages()->add($evaluation);
            }
            $assessmentAnswer = (new AssessmentAnswer())->setQuestion($question)->setStage($evaluation)->setAnswer($answer);
            $evaluation->getStageAssessmentAnswers()->add($assessmentAnswer);
        }
        $this->requestStack->getSession()->set('assessment', $assessment);
    }

    public function getSessionAssessment(): Assessment
    {
        return $this->requestStack->getSession()->get('assessment') ?? $this->createSessionAssessment();
    }

    public function createSessionAssessment(): Assessment
    {
        $assessment = new Assessment();
        $this->createSessionAssessmentStreams($assessment);
        $this->requestStack->getSession()->set('assessment', $assessment);

        return $assessment;
    }

    private function createSessionAssessmentStreams(Assessment $assessment): void
    {
        $streams = $this->metamodelService->getStreams();
        $assessmentStreams = [];
        foreach ($streams as $stream) {
            $assessmentStream = new AssessmentStream();
            $assessmentStream->setAssessment($assessment);
            $assessmentStream->setStream($stream);
            $assessmentStreams[] = $assessmentStream;
        }
        $assessment->setAssessmentAssessmentStreams(new ArrayCollection($assessmentStreams));
    }

    public function getAssessmentStream(Assessment $assessment, $streamExternalId): AssessmentStream
    {
        return $assessment->getAssessmentAssessmentStreams()->filter(
            function (AssessmentStream $assessmentStream) use ($streamExternalId) {
                return $streamExternalId === $assessmentStream->getStream()->getExternalId();
            }
        )->first();
    }
}
