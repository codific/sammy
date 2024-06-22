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

use App\Enum\MailingStatus;


// #BlockStart number=87 id=_19_0_3_40d01a2_1637589778949_213477_4862_#_0

// #BlockEnd number=87


#[ORM\Table(name: "`mailing`")]
#[ORM\Entity(repositoryClass: "App\Repository\MailingRepository")]
#[ORM\HasLifecycleCallbacks]
class Mailing extends AbstractEntity
// #BlockStart number=123123 id=_19_0_3_40d01a2_1637589778949_213477_4862_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\Column(name: "`surname`", type: Types::STRING, nullable: true)]
    protected ?string $surname = "";

    #[ORM\Column(name: "`email`", type: Types::STRING, nullable: true)]
    protected ?string $email = "";

    #[ORM\Column(name: "`subject`", type: Types::STRING, nullable: true)]
    protected ?string $subject = "";

    #[ORM\Column(name: "`message`", type: Types::TEXT, nullable: true)]
    protected ?string $message = "";

    #[ORM\Column(name: "`attachment`", type: Types::STRING, nullable: true)]
    protected ?string $attachment = "";

    #[ORM\Column(name: "`status`", enumType: MailingStatus::class)]
    protected MailingStatus $status = MailingStatus::NEW;

    #[ORM\ManyToOne(targetEntity: MailTemplate::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?MailTemplate $mailTemplate = null;

    #[ORM\Column(name: "`reply_to`", type: Types::STRING, nullable: true)]
    protected ?string $replyTo = "";

    #[ORM\Column(name: "`mail_from`", type: Types::STRING, nullable: true)]
    protected ?string $mailFrom = "";

    #[ORM\Column(name: "`mail_from_email`", type: Types::STRING, nullable: true)]
    protected ?string $mailFromEmail = "";

    #[ORM\Column(name: "`sent_date`", type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $sentDate = null;

    #[ORM\Column(name: "`status_msg`", type: Types::TEXT, nullable: true)]
    protected ?string $statusMsg = "";

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?User $user = null;


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
        $this->setStatus(MailingStatus::fromLabel($stringType));
    }

    /**
     * Return a list of all constants as strings
     */
    public static function getAllStatus(): array
    {
        return array_column(MailingStatus::cases(), "name", "value");
    }

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

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
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

    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setStatus(MailingStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): MailingStatus
    {
        return $this->status;
    }

    public function setMailTemplate(?MailTemplate $mailTemplate): self
    {
        $this->mailTemplate = $mailTemplate;

        return $this;
    }

    public function getMailTemplate(): ?MailTemplate
    {
        return $this->mailTemplate;
    }

    public function setReplyTo(?string $replyTo): self
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    public function setMailFrom(?string $mailFrom): self
    {
        $this->mailFrom = $mailFrom;

        return $this;
    }

    public function getMailFrom(): ?string
    {
        return $this->mailFrom;
    }

    public function setMailFromEmail(?string $mailFromEmail): self
    {
        $this->mailFromEmail = $mailFromEmail;

        return $this;
    }

    public function getMailFromEmail(): ?string
    {
        return $this->mailFromEmail;
    }

    public function setSentDate(?\DateTime $sentDate): self
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    public function getSentDate(): ?\DateTime
    {
        return $this->sentDate;
    }

    public function setStatusMsg(?string $statusMsg): self
    {
        $this->statusMsg = $statusMsg;

        return $this;
    }

    public function getStatusMsg(): ?string
    {
        return $this->statusMsg;
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


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Mailing $clone = null): Mailing
    {
        if ($clone === null) {
            $clone = new Mailing();
        }
        $clone->setName($this->name);
        $clone->setSurname($this->surname);
        $clone->setEmail($this->email);
        $clone->setSubject($this->subject);
        $clone->setMessage($this->message);
        $clone->setAttachment($this->attachment);
        $clone->setStatus($this->status);
        $clone->setMailTemplate($this->mailTemplate);
        $clone->setReplyTo($this->replyTo);
        $clone->setMailFrom($this->mailFrom);
        $clone->setMailFromEmail($this->mailFromEmail);
        $clone->setSentDate($this->sentDate);
        $clone->setStatusMsg($this->statusMsg);
        $clone->setUser($this->user);
// #BlockStart number=88 id=_19_0_3_40d01a2_1637589778949_213477_4862_#_2

// #BlockEnd number=88

        return $clone;
    }

    /**
     * Private to string method auto generated based on the UML properties
     * This is the new way of doing things.
     */
    public function toString(): string
    {
        return "{$this->name} {$this->surname}";
    }

    /**
     * https://symfony.com/doc/current/validation.html
     * we use php version for validation!!!
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('email', new Assert\Email(['mode' => 'strict']));

// #BlockStart number=89 id=_19_0_3_40d01a2_1637589778949_213477_4862_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=89
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_mailing.id",
            "_mailing.name",
            "_mailing.surname",
            "_mailing.email",
            "_mailing.subject",
            "_mailing.message",
            "_mailing.attachment",
            "_mailing.replyTo",
            "_mailing.mailFrom",
            "_mailing.mailFromEmail",
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
            "surname",
            "email",
            "subject",
            "message",
            "attachment",
            "status",
            "mailTemplate",
            "replyTo",
            "mailFrom",
            "mailFromEmail",
            "user",
        ];
    }

    #[Ignore]
    public function getReadOnlyFields(): array
    {
        return [
            "sentDate",
            "statusMsg",
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

// #BlockStart number=90 id=_19_0_3_40d01a2_1637589778949_213477_4862_#_4

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

// #BlockEnd number=90

}
