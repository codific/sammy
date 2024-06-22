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

use App\Enum\MailTemplateType;


// #BlockStart number=180 id=_19_0_3_40d01a2_1637589778954_493687_4863_#_0

// #BlockEnd number=180


#[ORM\Table(name: "`mail_template`")]
#[ORM\Entity(repositoryClass: "App\Repository\MailTemplateRepository")]
#[ORM\HasLifecycleCallbacks]
class MailTemplate extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1637589778954_493687_4863_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`type`", enumType: MailTemplateType::class)]
    protected MailTemplateType $type = MailTemplateType::NOTIFICATION;

    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\Column(name: "`subject`", type: Types::STRING, nullable: true)]
    protected ?string $subject = "";

    #[ORM\Column(name: "`message`", type: Types::TEXT, nullable: true)]
    protected ?string $message = "";



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
        $this->setType(MailTemplateType::fromLabel($stringType));
    }

    /**
     * Return a list of all constants as strings
     */
    public static function getAllType(): array
    {
        return array_column(MailTemplateType::cases(), "name", "value");
    }

    public function __construct()
    {
    }

    public function setType(MailTemplateType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): MailTemplateType
    {
        return $this->type;
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

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?MailTemplate $clone = null): MailTemplate
    {
        if ($clone === null) {
            $clone = new MailTemplate();
        }
        $clone->setType($this->type);
        $clone->setName($this->name);
        $clone->setSubject($this->subject);
        $clone->setMessage($this->message);
// #BlockStart number=181 id=_19_0_3_40d01a2_1637589778954_493687_4863_#_2

// #BlockEnd number=181

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

// #BlockStart number=182 id=_19_0_3_40d01a2_1637589778954_493687_4863_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=182
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_mail_template.id",
            "_mail_template.name",
            "_mail_template.subject",
            "_mail_template.message",
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
            "type",
            "name",
            "subject",
            "message",
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
    ];

// #BlockStart number=183 id=_19_0_3_40d01a2_1637589778954_493687_4863_#_4

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
        return ['message'];
    }

// #BlockEnd number=183

}
