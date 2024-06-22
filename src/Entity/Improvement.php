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

use App\Enum\ImprovementStatus;


// #BlockStart number=235 id=_19_0_3_40d01a2_1646749819286_690779_5058_#_0

// #BlockEnd number=235


#[ORM\Table(name: "`improvement`")]
#[ORM\Entity(repositoryClass: "App\Repository\ImprovementRepository")]
#[ORM\HasLifecycleCallbacks]
class Improvement extends Stage
// #BlockStart number=123123 id=_19_0_3_40d01a2_1646749819286_690779_5058_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\ManyToOne(targetEntity: AssessmentStream::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?AssessmentStream $new = null;

    #[ORM\Column(name: "`plan`", type: Types::TEXT, nullable: true)]
    protected ?string $plan = "";

    #[ORM\Column(name: "`status`", enumType: ImprovementStatus::class)]
    protected ImprovementStatus $status = ImprovementStatus::NEW;


    /**
     * Return the status in a human-readable string version
     */
    public function getStatusString(): string
    {
        return $this->status->label();
    }

    /**
     * Set the status value by string. Try to find the string value if none found set to 0.
     */
    #[Ignore]
    public function setStatusByString(string $stringType): void
    {
        $this->setStatus(ImprovementStatus::fromLabel($stringType));
    }

    /**
     * Return a list of all constants as strings
     */
    public static function getAllStatus(): array
    {
        return array_column(ImprovementStatus::cases(), "name", "value");
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function setNew(?AssessmentStream $new): self
    {
        $this->new = $new;

        return $this;
    }

    public function getNew(): ?AssessmentStream
    {
        return $this->new;
    }

    public function setPlan(?string $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setStatus(ImprovementStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ImprovementStatus
    {
        return $this->status;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Stage $clone = null): Stage
    {
        if ($clone === null) {
            $clone = new Improvement();
        }
        if ($clone instanceof Improvement) {
            $clone->setNew($this->new);
            $clone->setPlan($this->plan);
            $clone->setStatus($this->status);
        }
        $clone = parent::getCopy($clone);
// #BlockStart number=236 id=_19_0_3_40d01a2_1646749819286_690779_5058_#_2

// #BlockEnd number=236

        return $clone;
    }

    /**
     * Private to string method auto generated based on the UML properties
     * This is the new way of doing things.
     */
    public function toString(): string
    {
        return parent::toString();
    }

    /**
     * https://symfony.com/doc/current/validation.html
     * we use php version for validation!!!
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {

// #BlockStart number=237 id=_19_0_3_40d01a2_1646749819286_690779_5058_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=237
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_improvement.id",
            "_improvement.plan",
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
            "new",
            "plan",
            "status",
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


// #BlockStart number=238 id=_19_0_3_40d01a2_1646749819286_690779_5058_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public function getLessPurifiedFields(): array
    {
        return [];
    }

    /**
     * Get submittedBy Action Translation Key (Evaluated, Validated, Improved).
     */
    public function getSubmittedByActionTranslationKey(): string
    {
        return 'application.actions.improved_by';
    }

    public function getTypeTranslationKey(): string
    {
        return 'application.stage.improvement';
    }

// #BlockEnd number=238

}
