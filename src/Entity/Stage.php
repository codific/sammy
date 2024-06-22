<?php

/**
 * This is automatically generated file using the Codific Prototizer
 * PHP version 8
 * @category PHP
 * @author   CODIFIC <info@codific.com>
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Abstraction\AbstractEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\Ignore;


// #BlockStart number=208 id=_19_0_3_40d01a2_1646749246273_943490_4876_#_0

// #BlockEnd number=208


#[ORM\Table(name: "`stage`")]
#[ORM\Entity(repositoryClass: "App\Repository\StageRepository")]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "dType", type: Types::STRING)]
class Stage extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1646749246273_943490_4876_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\ManyToOne(targetEntity: AssessmentStream::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "assessmentStreamStages")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?AssessmentStream $assessmentStream = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "assignedToStages")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $assignedTo = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $submittedBy = null;

    #[ORM\Column(name: "`target_date`", type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTime $targetDate = null;

    #[ORM\Column(name: "`completed_at`", type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $completedAt = null;


    #[ORM\OneToMany(mappedBy: "stage", targetEntity: AssessmentAnswer::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["id" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $stageAssessmentAnswers;

    #[ORM\OneToMany(mappedBy: "stage", targetEntity: Remark::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["id" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $stageRemarks;

    #[ORM\OneToMany(mappedBy: "stage", targetEntity: Assignment::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["id" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $stageAssignments;


    public function __construct()
    {
        $this->stageAssessmentAnswers = new ArrayCollection();
        $this->stageRemarks = new ArrayCollection();
        $this->stageAssignments = new ArrayCollection();
    }

    public function setAssessmentStream(?AssessmentStream $assessmentStream): self
    {
        $this->assessmentStream = $assessmentStream;

        return $this;
    }

    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->assessmentStream;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setSubmittedBy(?User $submittedBy): self
    {
        $this->submittedBy = $submittedBy;

        return $this;
    }

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setTargetDate(?\DateTime $targetDate): self
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    public function getTargetDate(): ?\DateTime
    {
        return $this->targetDate;
    }

    public function setCompletedAt(?\DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    /**
     * Get Stage AssessmentAnswers
     * @return Collection<AssessmentAnswer>
     */
    public function getStageAssessmentAnswers(): Collection
    {
        return $this->stageAssessmentAnswers;
    }

    /**
     * Add AssessmentAnswers AssessmentAnswer
     */
    public function addStageAssessmentAnswer(AssessmentAnswer $assessmentAnswer): Stage
    {
        $this->stageAssessmentAnswers->add($assessmentAnswer);

        return $this;
    }

    /**
     * Get Stage Remarks
     * @return Collection<Remark>
     */
    public function getStageRemarks(): Collection
    {
        return $this->stageRemarks;
    }

    /**
     * Add Remarks Remark
     */
    public function addStageRemark(Remark $remark): Stage
    {
        $this->stageRemarks->add($remark);

        return $this;
    }

    /**
     * Get Stage Assignments
     * @return Collection<Assignment>
     */
    public function getStageAssignments(): Collection
    {
        return $this->stageAssignments;
    }

    /**
     * Add Assignments Assignment
     */
    public function addStageAssignment(Assignment $assignment): Stage
    {
        $this->stageAssignments->add($assignment);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Stage $clone = null): Stage
    {
        if ($clone === null) {
            $clone = new Stage();
        }
        $clone->setAssessmentStream($this->assessmentStream);
        $clone->setAssignedTo($this->assignedTo);
        $clone->setSubmittedBy($this->submittedBy);
        $clone->setTargetDate($this->targetDate);
        $clone->setCompletedAt($this->completedAt);
// #BlockStart number=209 id=_19_0_3_40d01a2_1646749246273_943490_4876_#_2

// #BlockEnd number=209

        return $clone;
    }

    /**
     * Private to string method auto generated based on the UML properties
     * This is the new way of doing things.
     */
    public function toString(): string
    {
        return "{$this->id}";
    }

    /**
     * https://symfony.com/doc/current/validation.html
     * we use php version for validation!!!
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {

// #BlockStart number=210 id=_19_0_3_40d01a2_1646749246273_943490_4876_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=210
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_stage.id",
        ];
    }

    #[Ignore]
    public function getUploadFields(): array
    {
        return [

        ];
    }

    #[Ignore]
    public function getModifiableFields(): array
    {
        return [
            "assessmentStream",
            "assignedTo",
            "submittedBy",
            "targetDate",
            "completedAt",
        ];
    }

    #[Ignore]
    public function getReadOnlyFields(): array
    {
        return [
        ];
    }

    #[Ignore]
    public function getParentClasses(): array
    {
        return [
            "assessmentStream",
            "user",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
        "stageAssessmentAnswers" => "stage",
        "stageRemarks" => "stage",
        "stageAssignments" => "stage",
    ];

// #BlockStart number=211 id=_19_0_3_40d01a2_1646749246273_943490_4876_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public function isActive(): bool
    {
        return $this->getCompletedAt() === null;
    }

    /**
     * Get submittedBy Action Translation Key (Evaluated, Validated, Improved).
     */
    public function getSubmittedByActionTranslationKey(): string
    {
        return '';
    }

    public function getTypeTranslationKey(): string
    {
        return '';
    }

    public const EVALUATION = 'Evaluation';
    public const VALIDATION = 'Validation';
    public const IMPROVEMENT = 'Improvement';
// #BlockEnd number=211

}
