<?php
declare(strict_types=1);

namespace App\Tests\builders;

use App\Entity\Assessment;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class AssessmentBuilder
{
    private ?EntityManagerInterface $entityManager;
    private ?Project $project = null;

    public function __construct(EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    public function withProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function build(bool $persist = true): Assessment
    {
        $assessment = new Assessment();
        $assessment->setProject($this->project ?? new Project());

        if ($persist && $this->entityManager !== null) {
            $this->entityManager->persist($assessment);
            $this->entityManager->flush();
        }

        return $assessment;
    }
}