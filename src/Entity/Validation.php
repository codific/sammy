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

use App\Enum\ValidationStatus;


// #BlockStart number=244 id=_19_0_3_40d01a2_1646749822065_209622_5088_#_0

// #BlockEnd number=244


#[ORM\Table(name: "`validation`")]
#[ORM\Entity(repositoryClass: "App\Repository\ValidationRepository")]
#[ORM\HasLifecycleCallbacks]
class Validation extends Stage
// #BlockStart number=123123 id=_19_0_3_40d01a2_1646749822065_209622_5088_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`status`", enumType: ValidationStatus::class)]
    protected ValidationStatus $status = ValidationStatus::NEW;

    #[ORM\Column(name: "`comment`", type: Types::TEXT, nullable: true)]
    protected ?string $comment = "";



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
        $this->setStatus(ValidationStatus::fromLabel($stringType));
    }

    /**
     * Return a list of all constants as strings
     */
    public static function getAllStatus(): array
    {
        return array_column(ValidationStatus::cases(), "name", "value");
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function setStatus(ValidationStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ValidationStatus
    {
        return $this->status;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Stage $clone = null): Stage
    {
        if ($clone === null) {
            $clone = new Validation();
        }
        if ($clone instanceof Validation) {
        $clone->setStatus($this->status);
        $clone->setComment($this->comment);
        }
        $clone = parent::getCopy($clone);
// #BlockStart number=245 id=_19_0_3_40d01a2_1646749822065_209622_5088_#_2

// #BlockEnd number=245

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

// #BlockStart number=246 id=_19_0_3_40d01a2_1646749822065_209622_5088_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=246
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_validation.id",
            "_validation.comment",
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
            "status",
            "comment",
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
            "assessmentStream",            "user",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


// #BlockStart number=247 id=_19_0_3_40d01a2_1646749822065_209622_5088_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string[]
     */
    public function getLessPurifiedFields(): array
    {
        return ['comment'];
    }

    /**
     * Get submittedBy Action Translation Key (Evaluated, Validated, Improved).
     */
    public function getSubmittedByActionTranslationKey(): string
    {
        return match ($this->status) {
            ValidationStatus::AUTO_ACCEPTED => 'application.actions.auto_validated',
            ValidationStatus::ACCEPTED => 'application.actions.validated_by',
            default => ''
        };
    }

    public function getTypeTranslationKey(): string
    {
        return 'application.stage.validation';
    }
// #BlockEnd number=247

}
