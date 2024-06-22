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


// #BlockStart number=217 id=_19_0_3_40d01a2_1646749661905_888722_4949_#_0

// #BlockEnd number=217


#[ORM\Table(name: "`remark`")]
#[ORM\Entity(repositoryClass: "App\Repository\RemarkRepository")]
#[ORM\HasLifecycleCallbacks]
class Remark extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1646749661905_888722_4949_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`text`", type: Types::TEXT, nullable: true)]
    protected ?string $text = "";

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Stage::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "stageRemarks")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Stage $stage = null;

    #[ORM\Column(name: "`title`", type: Types::STRING, nullable: true)]
    protected ?string $title = "";

    #[ORM\Column(name: "`files`", type: Types::JSON)]
    protected array $files = [];

    #[ORM\Column(name: "`file`", type: Types::TEXT, nullable: true)]
    protected ?string $file = "";

    #[Ignore]
    #[ORM\OneToMany(mappedBy: "remark", targetEntity: MaturityLevelRemark::class, cascade: ["persist"], orphanRemoval: false)]
    protected Collection $remarkMaturityLevelRemarks;


    public function __construct()
    {
        $this->remarkMaturityLevelRemarks = new ArrayCollection();
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
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

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFile(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Get MaturityLevelRemarks that are accessible via the many-to-many relationship
     * @return Collection<MaturityLevelRemark>
     */
    public function getMaturityLevelRemarks(): Collection
    {
        return $this->remarkMaturityLevelRemarks;
    }

    public function addRemarkMaturityLevelRemark(MaturityLevelRemark $maturityLevelRemark): Remark
    {
        $this->remarkMaturityLevelRemarks->add($maturityLevelRemark);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Remark $clone = null): Remark
    {
        if ($clone === null) {
            $clone = new Remark();
        }
        $clone->setText($this->text);
        $clone->setUser($this->user);
        $clone->setStage($this->stage);
        $clone->setTitle($this->title);
        $clone->setFiles($this->files);
        $clone->setFile($this->file);
// #BlockStart number=218 id=_19_0_3_40d01a2_1646749661905_888722_4949_#_2

// #BlockEnd number=218

        return $clone;
    }

    /**
     * Private to string method auto generated based on the UML properties
     * This is the new way of doing things.
     */
    public function toString(): string
    {
        return "{$this->title}";
    }

    /**
     * https://symfony.com/doc/current/validation.html
     * we use php version for validation!!!
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {

// #BlockStart number=219 id=_19_0_3_40d01a2_1646749661905_888722_4949_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=219
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_remark.id",
            "_remark.text",
            "_remark.title",
        ];
    }

    #[Ignore]
    public function getUploadFields(): array
    {
        return [
            "file",
        ];
    }

    #[Ignore]
    public function getModifiableFields(): array
    {
        return [
            "text",
            "user",
            "stage",
            "title",
            "files",
            "file",
        ];
    }

    #[Ignore]
    public function getReadOnlyFields(): array
    {
        return [
            "file",
        ];
    }

    #[Ignore]
    public function getParentClasses(): array
    {
        return [
            "stage",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
        "maturityLevelRemarks" => "MaturityLevelRemark",
    ];


    #[Ignore]
    public static array $childProperties = [
    ];

// #BlockStart number=220 id=_19_0_3_40d01a2_1646749661905_888722_4949_#_4

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

// #BlockEnd number=220

}
