<?php
declare(strict_types=1);

namespace App\Tests\builders;

use App\Entity\Assessment;
use App\Entity\AssessmentStream;
use App\Entity\Stream;
use App\Enum\AssessmentStatus;
use Doctrine\ORM\EntityManagerInterface;

class AssessmentStreamBuilder
{
    private ?EntityManagerInterface $entityManager;
    private ?Stream $stream;
    private ?Assessment $assessment;
    private AssessmentStatus $assessmentStatus;
    private ?\DateTime $expirationDate;
    private float $score;
    private ?string $externalId;

    public function __construct(EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    public function withStream(?Stream $stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    public function withAssessment(?Assessment $assessment): self
    {
        $this->assessment = $assessment;

        return $this;
    }

    public function withAssessmentStatus(AssessmentStatus $assessmentStatus): self
    {
        $this->assessmentStatus = $assessmentStatus;

        return $this;
    }

    public function withExpirationDate(?\DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function withScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function withExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }


    public function build(bool $persist = true): AssessmentStream
    {
        $assessmentStream = new AssessmentStream();
        $assessmentStream->setStream($this->stream ?? new Stream());
        $assessmentStream->setAssessment($this->assessment ?? new Assessment());
        $assessmentStream->setStatus($this->assessmentStatus ?? AssessmentStatus::NEW);
        $assessmentStream->setExpirationDate($this->expirationDate ?? new \DateTime('+1 year'));
        $assessmentStream->setScore($this->score ?? 0.0);
        $assessmentStream->setExternalId($this->externalId ?? null);

        if ($persist && $this->entityManager !== null) {
            $this->entityManager->persist($assessmentStream);
            $this->entityManager->flush();
        }

        return $assessmentStream;
    }
}