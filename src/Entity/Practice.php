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



// #BlockStart number=53 id=_19_0_3_40d01a2_1635864210817_220463_6011_#_0

// #BlockEnd number=53


#[ORM\Table(name: "`practice`")]
#[ORM\Entity(repositoryClass: "App\Repository\PracticeRepository")]
#[ORM\HasLifecycleCallbacks]
class Practice extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635864210817_220463_6011_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\ManyToOne(targetEntity: BusinessFunction::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "businessFunctionPractices")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?BusinessFunction $businessFunction = null;

    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\Column(name: "`short_name`", type: Types::STRING, nullable: true)]
    protected ?string $shortName = "";

    #[ORM\Column(name: "`short_description`", type: Types::TEXT, nullable: true)]
    protected ?string $shortDescription = "";

    #[ORM\Column(name: "`long_description`", type: Types::TEXT, nullable: true)]
    protected ?string $longDescription = "";

    #[ORM\Column(name: "`order`", type: Types::INTEGER)]
    protected int $order = 0;

    #[ORM\Column(name: "`external_id`", type: Types::STRING, nullable: true)]
    protected ?string $externalId = "";

    #[ORM\Column(name: "`icon`", type: Types::STRING, nullable: true)]
    protected ?string $icon = "";

    #[ORM\Column(name: "`slug`", type: Types::STRING, nullable: true)]
    protected ?string $slug = "";

    #[ORM\OneToMany(mappedBy: "practice", targetEntity: Stream::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["order" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $practiceStreams;

    #[ORM\OneToMany(mappedBy: "practice", targetEntity: PracticeLevel::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["id" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $practicePracticeLevels;



    public function __construct()
    {
        $this->practiceStreams = new ArrayCollection();
        $this->practicePracticeLevels = new ArrayCollection();
    }

    public function setBusinessFunction(?BusinessFunction $businessFunction): self
    {
        $this->businessFunction = $businessFunction;

        return $this;
    }

    public function getBusinessFunction(): ?BusinessFunction
    {
        return $this->businessFunction;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setShortName(?string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setLongDescription(?string $longDescription): self
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
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

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Get Practice Streams
     * @return Collection<Stream>
     */
    public function getPracticeStreams(): Collection
    {
        return $this->practiceStreams;
    }

    /**
     * Add Streams Stream
     */
    public function addPracticeStream(Stream $stream): Practice
    {
        $this->practiceStreams->add($stream);

        return $this;
    }

    /**
     * Get Practice PracticeLevels
     * @return Collection<PracticeLevel>
     */
    public function getPracticePracticeLevels(): Collection
    {
        return $this->practicePracticeLevels;
    }

    /**
     * Add PracticeLevels PracticeLevel
     */
    public function addPracticePracticeLevel(PracticeLevel $practiceLevel): Practice
    {
        $this->practicePracticeLevels->add($practiceLevel);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Practice $clone = null): Practice
    {
        if ($clone === null) {
            $clone = new Practice();
        }
        $clone->setBusinessFunction($this->businessFunction);
        $clone->setName($this->name);
        $clone->setShortName($this->shortName);
        $clone->setShortDescription($this->shortDescription);
        $clone->setLongDescription($this->longDescription);
        $clone->setOrder($this->order);
        $clone->setExternalId($this->externalId);
        $clone->setIcon($this->icon);
        $clone->setSlug($this->slug);
// #BlockStart number=54 id=_19_0_3_40d01a2_1635864210817_220463_6011_#_2

// #BlockEnd number=54

        return $clone;
    }

    /**
     * Private to string method auto generated based on the UML properties
     * This is the new way of doing things.
     */
    public function toString(): string
    {
        return "{$this->name}";
    }

    /**
     * https://symfony.com/doc/current/validation.html
     * we use php version for validation!!!
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());

// #BlockStart number=55 id=_19_0_3_40d01a2_1635864210817_220463_6011_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=55
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_practice.id",
            "_practice.name",
            "_practice.shortName",
            "_practice.shortDescription",
            "_practice.longDescription",
            "_practice.order",
            "_practice.icon",
            "_practice.slug",
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
            "businessFunction",
            "name",
            "shortName",
            "shortDescription",
            "longDescription",
            "order",
            "icon",
            "slug",
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
            "businessFunction",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
        "practiceStreams" => "practice",
        "practicePracticeLevels" => "practice",
    ];

// #BlockStart number=56 id=_19_0_3_40d01a2_1635864210817_220463_6011_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public function getNameKey(): string
    {
        return $this->getBusinessFunction()->getNameKey().'-'.$this->getShortName();
    }
// #BlockEnd number=56

}
