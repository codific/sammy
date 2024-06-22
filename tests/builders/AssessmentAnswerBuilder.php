<?php
declare(strict_types=1);

namespace App\Tests\builders;

use App\Entity\Answer;
use App\Entity\AssessmentAnswer;
use App\Entity\Question;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\AssessmentAnswerType;
use Doctrine\ORM\EntityManagerInterface;

class AssessmentAnswerBuilder
{
    private ?EntityManagerInterface $entityManager;
    private Answer $answer;
    private User $user;
    private ?Question $question;
    private AssessmentAnswerType $assessmentAnswerType;
    private ?Stage $stage;
    private array $criteria;

    public function __construct(EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    public function withAnswer(Answer $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function withUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function withQuestion(?Question $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function withAssessmentAnswerType(AssessmentAnswerType $assessmentAnswerType): self
    {
        $this->assessmentAnswerType = $assessmentAnswerType;

        return $this;
    }

    public function withStage(?Stage $stage): self
    {
        $this->stage = $stage;

        return $this;
    }

    public function withCriteria(array $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }


    public function build(bool $persist = true): AssessmentAnswer
    {
        $assessmentAnswer = new AssessmentAnswer();
        $assessmentAnswer->setAnswer($this->answer ?? new Answer());
        $assessmentAnswer->setUser($this->user ?? (new UserBuilder())->build());
        $assessmentAnswer->setQuestion($this->question ?? new Question());
        $assessmentAnswer->setType($this->assessmentAnswerType ?? AssessmentAnswerType::CURRENT);
        $assessmentAnswer->setStage($this->stage ?? new Stage());
        $assessmentAnswer->setCriteria($this->criteria ?? []);

        if ($persist && $this->entityManager !== null) {
            $this->entityManager->persist($assessmentAnswer);
            $this->entityManager->flush();
        }

        return $assessmentAnswer;
    }
}