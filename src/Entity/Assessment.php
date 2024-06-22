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


// #BlockStart number=125 id=_19_0_3_40d01a2_1635865714358_177864_6732_#_0

// #BlockEnd number=125


#[ORM\Table(name: "`assessment`")]
#[ORM\Entity(repositoryClass: "App\Repository\AssessmentRepository")]
#[ORM\HasLifecycleCallbacks]
class Assessment extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635865714358_177864_6732_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\OneToOne(targetEntity: Project::class, cascade: ["persist"], fetch: "EAGER", mappedBy: "assessment")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Project $project = null;

    #[ORM\OneToMany(mappedBy: "assessment", targetEntity: AssessmentStream::class, cascade: ["persist"], fetch: "LAZY", orphanRemoval: false)]
    #[ORM\OrderBy(["id" => "ASC"])]
    #[MaxDepth(1)]
    protected Collection $assessmentAssessmentStreams;


    public function __construct()
    {
        $this->assessmentAssessmentStreams = new ArrayCollection();
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * Get Assessment AssessmentStreams
     * @return Collection<AssessmentStream>
     */
    public function getAssessmentAssessmentStreams(): Collection
    {
        return $this->assessmentAssessmentStreams;
    }

    /**
     * Add AssessmentStreams AssessmentStream
     */
    public function addAssessmentAssessmentStream(AssessmentStream $assessmentStream): Assessment
    {
        $this->assessmentAssessmentStreams->add($assessmentStream);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Assessment $clone = null): Assessment
    {
        if ($clone === null) {
            $clone = new Assessment();
        }
        $clone->setProject($this->project);
// #BlockStart number=126 id=_19_0_3_40d01a2_1635865714358_177864_6732_#_2

// #BlockEnd number=126

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

// #BlockStart number=127 id=_19_0_3_40d01a2_1635865714358_177864_6732_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=127
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_assessment.id",
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
            "project",
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

        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
        "assessmentAssessmentStreams" => "assessment",
    ];

// #BlockStart number=128 id=_19_0_3_40d01a2_1635865714358_177864_6732_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Get Assessment AssessmentStreams.
     *
     * @return void
     */
    public function setAssessmentAssessmentStreams(Collection $assessmentAssessmentStreams)
    {
        $this->assessmentAssessmentStreams = $assessmentAssessmentStreams;
    }

    public function getLastUpdate(): ?\DateTime
    {
        if (sizeof($this->assessmentAssessmentStreams) > 0) {
            return $this->assessmentAssessmentStreams[sizeof($this->assessmentAssessmentStreams) - 1]->getUpdatedAt();
        }

        return null;
    }

// #BlockEnd number=128

}
