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

use App\Enum\AssessmentAnswerType;


// #BlockStart number=134 id=_19_0_3_40d01a2_1635865758967_363555_6779_#_0

// #BlockEnd number=134


#[ORM\Table(name: "`assessment_answer`")]
#[ORM\Entity(repositoryClass: "App\Repository\AssessmentAnswerRepository")]
#[ORM\HasLifecycleCallbacks]
class AssessmentAnswer extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1635865758967_363555_6779_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\ManyToOne(targetEntity: Answer::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Answer $answer = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Question::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Question $question = null;

    #[ORM\Column(name: "`type`", enumType: AssessmentAnswerType::class)]
    protected AssessmentAnswerType $type = AssessmentAnswerType::CURRENT;

    #[ORM\ManyToOne(targetEntity: Stage::class, cascade: ["persist"], fetch: "EAGER", inversedBy: "stageAssessmentAnswers")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Stage $stage = null;

    #[ORM\Column(name: "`criteria`", type: Types::JSON)]
    protected array $criteria = [];


    /**
     * Return the type in a human-readable string version
     */
    public function getTypeString(): string
    {
        return $this->type->label();
    }

    /**
     * Set the type value by string. Try to find the string value if none found set to 0.
     */
    #[Ignore]
    public function setTypeByString(string $stringType): void
    {
        $this->setType(AssessmentAnswerType::fromLabel($stringType));
    }

    /**
     * Return a list of all constants as strings
     */
    public static function getAllType(): array
    {
        return array_column(AssessmentAnswerType::cases(), "name", "value");
    }

    public function __construct()
    {
    }

    public function setAnswer(?Answer $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getAnswer(): ?Answer
    {
        return $this->answer;
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

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setType(AssessmentAnswerType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): AssessmentAnswerType
    {
        return $this->type;
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

    public function setCriteria(array $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?AssessmentAnswer $clone = null): AssessmentAnswer
    {
        if ($clone === null) {
            $clone = new AssessmentAnswer();
        }
        $clone->setAnswer($this->answer);
        $clone->setUser($this->user);
        $clone->setQuestion($this->question);
        $clone->setType($this->type);
        $clone->setStage($this->stage);
        $clone->setCriteria($this->criteria);
// #BlockStart number=135 id=_19_0_3_40d01a2_1635865758967_363555_6779_#_2

// #BlockEnd number=135

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

// #BlockStart number=136 id=_19_0_3_40d01a2_1635865758967_363555_6779_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=136
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_assessment_answer.id",
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
            "answer",
            "user",
            "question",
            "type",
            "stage",
            "criteria",
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
            "stage",
        ];
    }

    #[Ignore]
    public static array $manyToManyProperties = [
    ];


    #[Ignore]
    public static array $childProperties = [
    ];

// #BlockStart number=137 id=_19_0_3_40d01a2_1635865758967_363555_6779_#_4

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
     * Get assessmentStream.
     */
    public function getAssessmentStream(): ?AssessmentStream
    {
        return $this->stage->getAssessmentStream();
    }

// #BlockEnd number=137

}
