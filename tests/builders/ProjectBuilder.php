<?php
declare(strict_types=1);

namespace App\Tests\builders;

use App\Entity\Assessment;
use App\Entity\Metamodel;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ProjectBuilder
{
    private ?EntityManagerInterface $entityManager;
    private string $name;
    private float $validationTreshold;
    private ?Assessment $assessment;
    private ?string $description;
    private bool $template;
    private ?Project $templateProject;
    private ?Metamodel $metamodel;
    private ?string $externalId;

    public function __construct(EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withValidationTreshold(float $validationTreshold): self
    {
        $this->validationTreshold = $validationTreshold;

        return $this;
    }

    public function withAssessment(?Assessment $assessment): self
    {
        $this->assessment = $assessment;

        return $this;
    }

    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function withTemplate(bool $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function withTemplateProject(?Project $templateProject): self
    {
        $this->templateProject = $templateProject;

        return $this;
    }

    public function withMetamodel(?Metamodel $metamodel): self
    {
        $this->metamodel = $metamodel;

        return $this;
    }

    public function withExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function build(bool $persist = true): Project
    {
        $project = new Project();
        $project->setName($this->name ?? bin2hex(random_bytes(5))."projectName");
        $project->setValidationThreshold($this->validationTreshold ?? 0.0);
        $project->setAssessment($this->assessment ?? null);
        $project->setDescription($this->description ?? null);
        $project->setTemplate($this->template ?? false);
        $project->setTemplateProject($this->templateProject ?? null);
        $project->setMetamodel($this->metamodel ?? null);
        $project->setExternalId($this->externalId ?? null);

        if ($persist && $this->entityManager !== null) {
            $this->entityManager->persist($project);
            $this->entityManager->flush();
        }

        return $project;
    }
}