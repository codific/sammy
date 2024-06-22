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


// #BlockStart number=15 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_0
use App\Entity\Abstraction\UserModifiableFieldsInterface;

// #BlockEnd number=15


#[ORM\Table(name: "`project`")]
#[ORM\Entity(repositoryClass: "App\Repository\ProjectRepository")]
#[ORM\HasLifecycleCallbacks]
class Project extends AbstractEntity implements UserModifiableFieldsInterface
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_1
    // additional implements go here
// #BlockEnd number=123123
{
    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\Column(name: "`validation_threshold`", type: Types::DECIMAL, precision: 10, scale: 2)]
    protected float $validationThreshold = 0.0;

    #[ORM\OneToOne(targetEntity: Assessment::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "project")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Assessment $assessment = null;

    #[ORM\Column(name: "`description`", type: Types::TEXT, nullable: true)]
    protected ?string $description = "";

    #[ORM\Column(name: "`template`", type: Types::BOOLEAN)]
    protected bool $template = false;

    #[ORM\ManyToOne(targetEntity: Project::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Project $templateProject = null;

    #[ORM\ManyToOne(targetEntity: Metamodel::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Metamodel $metamodel = null;

    #[ORM\Column(name: "`external_id`", type: Types::STRING, nullable: true)]
    protected ?string $externalId = "";


    public function __construct()
    {
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

    public function setValidationThreshold(float $validationThreshold): self
    {
        $this->validationThreshold = $validationThreshold;

        return $this;
    }

    public function getValidationThreshold(): float
    {
        return $this->validationThreshold;
    }

    public function setAssessment(?Assessment $assessment): self
    {
        $this->assessment = $assessment;

        return $this;
    }

    public function getAssessment(): ?Assessment
    {
        return $this->assessment;
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

    public function setTemplate(bool $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplateProject(?Project $templateProject): self
    {
        $this->templateProject = $templateProject;

        return $this;
    }

    public function getTemplateProject(): ?Project
    {
        return $this->templateProject;
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
    public function getCopy(?Project $clone = null): Project
    {
        if ($clone === null) {
            $clone = new Project();
        }
        $clone->setName($this->name);
        $clone->setValidationThreshold($this->validationThreshold);
        $clone->setAssessment($this->assessment);
        $clone->setDescription($this->description);
        $clone->setTemplate($this->template);
        $clone->setTemplateProject($this->templateProject);
        $clone->setMetamodel($this->metamodel);
        $clone->setExternalId($this->externalId);
// #BlockStart number=16 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_2

// #BlockEnd number=16

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

// #BlockStart number=17 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=17
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_project.id",
            "_project.name",
            "_project.description",
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
            "validationThreshold",
            "assessment",
            "description",
            "template",
            "templateProject",
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
    ];

// #BlockStart number=18 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Is template, alias to getTemplate.
     */
    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function getUserModifiableFields(): array
    {
        return [
            'name',
            'validationThreshold',
            'description',
            'templateProject',
        ];
    }

// #BlockEnd number=18

}
