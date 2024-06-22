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



// #BlockStart number=62 id=_19_0_3_40d01a2_1635864239167_130089_6058_#_0

// #BlockEnd number=62


#[ORM\Table(name: "`stream`")]
#[ORM\Entity(repositoryClass: "App\Repository\StreamRepository")]
#[ORM\HasLifecycleCallbacks]
class Stream extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635864239167_130089_6058_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\ManyToOne(targetEntity: Practice::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "practiceStreams")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Practice $practice = null;

    #[ORM\Column(name: "`description`", type: Types::TEXT, nullable: true)]
    protected ?string $description = "";

    #[ORM\Column(name: "`order`", type: Types::INTEGER)]
    protected int $order = 0;

    #[ORM\Column(name: "`external_id`", type: Types::STRING, nullable: true)]
    protected ?string $externalId = "";

    #[ORM\Column(name: "`weight`", type: Types::DECIMAL, precision: 10, scale: 2)]
    protected float $weight = 0.0;

    #[ORM\OneToMany(mappedBy: "stream", targetEntity: Activity::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["id" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $streamActivities;



    public function __construct()
    {
        $this->streamActivities = new ArrayCollection();
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

    public function setPractice(?Practice $practice): self
    {
        $this->practice = $practice;

        return $this;
    }

    public function getPractice(): ?Practice
    {
        return $this->practice;
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

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * Get Stream Activities
     * @return Collection<Activity>
     */
    public function getStreamActivities(): Collection
    {
        return $this->streamActivities;
    }

    /**
     * Add Activities Activity
     */
    public function addStreamActivity(Activity $activity): Stream
    {
        $this->streamActivities->add($activity);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Stream $clone = null): Stream
    {
        if ($clone === null) {
            $clone = new Stream();
        }
        $clone->setName($this->name);
        $clone->setPractice($this->practice);
        $clone->setDescription($this->description);
        $clone->setOrder($this->order);
        $clone->setExternalId($this->externalId);
        $clone->setWeight($this->weight);
// #BlockStart number=63 id=_19_0_3_40d01a2_1635864239167_130089_6058_#_2

// #BlockEnd number=63

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

// #BlockStart number=64 id=_19_0_3_40d01a2_1635864239167_130089_6058_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=64
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_stream.id",
            "_stream.name",
            "_stream.description",
            "_stream.order",
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
            "practice",
            "description",
            "order",
            "weight",
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
        "streamActivities" => "stream",
    ];

// #BlockStart number=65 id=_19_0_3_40d01a2_1635864239167_130089_6058_#_4
    public const STREAMLETTER_A = 1;
    public const STREAMLETTER_B = 2;

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Return the stream letter drived from order.
     */
    public function getLetter(): string
    {
        return match ($this->order) {
            Stream::STREAMLETTER_A => 'A',
            Stream::STREAMLETTER_B => 'B',
            default => 'NOT SPECIFIED',
        };
    }

    public function getNameKey(): string
    {
        $numberOfStreamsInPractice = sizeof($this->getPractice()->getPracticeStreams());
        $nameKey = $this->getPractice()->getNameKey();
        if ($numberOfStreamsInPractice > 1) {
            $nameKey .= "-" . $this->getLetter();
        }
        return $nameKey;
    }

    /**
     * Convert from letter to order.
     *
     * @param $letter string
     *
     * @return ?int
     */
    public static function getOrderByLetter(string $letter): ?int
    {
        return match ($letter) {
            'A' => Stream::STREAMLETTER_A,
            'B' => Stream::STREAMLETTER_B,
            default => null,
        };
    }

    /**
     * Get name.
     */
    public function getTrimmedName(): ?string
    {
        $name = $this->name;
        if (strlen($name) > 25 && str_contains($name, '/')) {
            $name = ltrim(substr($name, strpos($name, '/') + 1));
        }

        return str_replace(' and ', ' & ', $name);
    }
// #BlockEnd number=65

}
