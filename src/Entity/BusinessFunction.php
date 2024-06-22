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



// #BlockStart number=44 id=_19_0_3_40d01a2_1635864197250_327408_5981_#_0

// #BlockEnd number=44


#[ORM\Table(name: "`business_function`")]
#[ORM\Entity(repositoryClass: "App\Repository\BusinessFunctionRepository")]
#[ORM\HasLifecycleCallbacks]
class BusinessFunction extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635864197250_327408_5981_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\Column(name: "`description`", type: Types::TEXT, nullable: true)]
    protected ?string $description = "";

    #[ORM\Column(name: "`color`", type: Types::STRING, nullable: true)]
    protected ?string $color = "";

    #[ORM\Column(name: "`logo`", type: Types::STRING, nullable: true)]
    protected ?string $logo = "";

    #[ORM\Column(name: "`order`", type: Types::INTEGER)]
    protected int $order = 0;

    #[ORM\Column(name: "`external_id`", type: Types::STRING, nullable: true)]
    protected ?string $externalId = "";

    #[ORM\Column(name: "`icon`", type: Types::STRING, nullable: true)]
    protected ?string $icon = "";

    #[ORM\ManyToOne(targetEntity: Metamodel::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Metamodel $metamodel = null;

    #[ORM\OneToMany(mappedBy: "businessFunction", targetEntity: Practice::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["order" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $businessFunctionPractices;



    public function __construct()
    {
        $this->businessFunctionPractices = new ArrayCollection();
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

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
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

    public function setMetamodel(?Metamodel $metamodel): self
    {
        $this->metamodel = $metamodel;

        return $this;
    }

    public function getMetamodel(): ?Metamodel
    {
        return $this->metamodel;
    }

    /**
     * Get BusinessFunction Practices
     * @return Collection<Practice>
     */
    public function getBusinessFunctionPractices(): Collection
    {
        return $this->businessFunctionPractices;
    }

    /**
     * Add Practices Practice
     */
    public function addBusinessFunctionPractice(Practice $practice): BusinessFunction
    {
        $this->businessFunctionPractices->add($practice);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?BusinessFunction $clone = null): BusinessFunction
    {
        if ($clone === null) {
            $clone = new BusinessFunction();
        }
        $clone->setName($this->name);
        $clone->setDescription($this->description);
        $clone->setColor($this->color);
        $clone->setLogo($this->logo);
        $clone->setOrder($this->order);
        $clone->setExternalId($this->externalId);
        $clone->setIcon($this->icon);
        $clone->setMetamodel($this->metamodel);
// #BlockStart number=45 id=_19_0_3_40d01a2_1635864197250_327408_5981_#_2

// #BlockEnd number=45

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

// #BlockStart number=46 id=_19_0_3_40d01a2_1635864197250_327408_5981_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=46
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_business_function.id",
            "_business_function.name",
            "_business_function.description",
            "_business_function.color",
            "_business_function.logo",
            "_business_function.order",
            "_business_function.icon",
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
            "name",
            "description",
            "color",
            "logo",
            "order",
            "icon",
            "metamodel",
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

        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
        "businessFunctionPractices" => "businessFunction",
    ];

// #BlockStart number=47 id=_19_0_3_40d01a2_1635864197250_327408_5981_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns the short name of the businessFunction (First letter).
     */
    public function getShortName(): string
    {
        return $this->name[0];
    }

    public function getNameKey(): string
    {
        return $this->getShortName();
    }

// #BlockEnd number=47

}
