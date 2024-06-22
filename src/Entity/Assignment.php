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


// #BlockStart number=287 id=_19_0_3_40d01a2_1652174082929_735674_4866_#_0

// #BlockEnd number=287


#[ORM\Table(name: "`assignment`")]
#[ORM\Entity(repositoryClass: "App\Repository\AssignmentRepository")]
#[ORM\HasLifecycleCallbacks]
class Assignment extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1652174082929_735674_4866_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "userAssignments")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Stage::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "stageAssignments")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Stage $stage = null;

    #[ORM\Column(name: "`remark`", type: Types::TEXT, nullable: true)]
    protected ?string $remark = "";

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $assignedBy = null;

    #[ORM\Column(name: "`completed_at`", type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $completedAt = null;

    #[ORM\Column(name: "`target_date`", type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTime $targetDate = null;

    public function __construct()
    {
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setStage(?Stage $stage): self
    {
        $this->stage = $stage;

        return $this;
    }

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;

        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setAssignedBy(?User $assignedBy): self
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    public function getAssignedBy(): ?User
    {
        return $this->assignedBy;
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

    public function setTargetDate(?\DateTime $targetDate): self
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    public function getTargetDate(): ?\DateTime
    {
        return $this->targetDate;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Assignment $clone = null): Assignment
    {
        if ($clone === null) {
            $clone = new Assignment();
        }
        $clone->setUser($this->user);
        $clone->setStage($this->stage);
        $clone->setRemark($this->remark);
        $clone->setAssignedBy($this->assignedBy);
        $clone->setCompletedAt($this->completedAt);
        $clone->setTargetDate($this->targetDate);
// #BlockStart number=288 id=_19_0_3_40d01a2_1652174082929_735674_4866_#_2

// #BlockEnd number=288

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

// #BlockStart number=289 id=_19_0_3_40d01a2_1652174082929_735674_4866_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=289
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_assignment.id",
            "_assignment.remark",
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
            "user",
            "stage",
            "remark",
            "assignedBy",
            "completedAt",
            "targetDate",
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
            "user",
            "stage",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
    ];

// #BlockStart number=290 id=_19_0_3_40d01a2_1652174082929_735674_4866_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

// #BlockEnd number=290

}
