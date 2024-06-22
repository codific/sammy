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



// #BlockStart number=80 id=_19_0_3_40d01a2_1635864815057_862891_6258_#_0

// #BlockEnd number=80


#[ORM\Table(name: "`practice_level`")]
#[ORM\Entity(repositoryClass: "App\Repository\PracticeLevelRepository")]
#[ORM\HasLifecycleCallbacks]
class PracticeLevel extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635864815057_862891_6258_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\ManyToOne(targetEntity: MaturityLevel::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?MaturityLevel $maturityLevel = null;

    #[ORM\ManyToOne(targetEntity: Practice::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "practicePracticeLevels")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Practice $practice = null;

    #[ORM\Column(name: "`objective`", type: Types::STRING, nullable: true)]
    protected ?string $objective = "";

    #[ORM\Column(name: "`external_id`", type: Types::STRING, nullable: true)]
    protected ?string $externalId = "";



    public function __construct()
    {
    }

    public function setMaturityLevel(?MaturityLevel $maturityLevel): self
    {
        $this->maturityLevel = $maturityLevel;

        return $this;
    }

    public function getMaturityLevel(): ?MaturityLevel
    {
        return $this->maturityLevel;
    }

    public function setPractice(?Practice $practice): self
    {
        $this->practice = $practice;

        return $this;
    }

    public function getPractice(): ?Practice
    {
        return $this->practice;
    }

    public function setObjective(?string $objective): self
    {
        $this->objective = $objective;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?PracticeLevel $clone = null): PracticeLevel
    {
        if ($clone === null) {
            $clone = new PracticeLevel();
        }
        $clone->setMaturityLevel($this->maturityLevel);
        $clone->setPractice($this->practice);
        $clone->setObjective($this->objective);
        $clone->setExternalId($this->externalId);
// #BlockStart number=81 id=_19_0_3_40d01a2_1635864815057_862891_6258_#_2

// #BlockEnd number=81

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

// #BlockStart number=82 id=_19_0_3_40d01a2_1635864815057_862891_6258_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=82
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_practice_level.id",
            "_practice_level.objective",
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
            "maturityLevel",
            "practice",
            "objective",
        ];
    }

    #[Ignore]
    public function getReadOnlyFields(): array
    {
        return [
            "externalId",
        ];
    }

    #[Ignore]
    public function getParentClasses(): array
    {
        return [
            "practice",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
    ];

// #BlockStart number=83 id=_19_0_3_40d01a2_1635864815057_862891_6258_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

// #BlockEnd number=83

}
