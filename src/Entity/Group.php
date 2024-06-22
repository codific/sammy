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


// #BlockStart number=253 id=_19_0_3_40d01a2_1646802256748_639463_4967_#_0
use App\Entity\Abstraction\UserModifiableFieldsInterface;

// #BlockEnd number=253


#[ORM\Table(name: "`group`")]
#[ORM\Entity(repositoryClass: "App\Repository\GroupRepository")]
#[ORM\HasLifecycleCallbacks]
class Group extends AbstractEntity implements UserModifiableFieldsInterface
// #BlockStart number=123123 id=_19_0_3_40d01a2_1646802256748_639463_4967_#_1
    // additional implements go here
// #BlockEnd number=123123
{

    #[ORM\Column(name: "`name`", type: Types::STRING, nullable: true)]
    protected ?string $name = "";

    #[ORM\ManyToOne(targetEntity: Group::class, cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[MaxDepth(1)]
    protected ?Group $parent = null;

    #[Ignore]
    #[ORM\OneToMany(mappedBy: "group", targetEntity: GroupProject::class, cascade: ["persist"], orphanRemoval: false)]
    protected Collection $groupGroupProjects;
    #[Ignore]
    #[ORM\OneToMany(mappedBy: "group", targetEntity: GroupUser::class, cascade: ["persist"], orphanRemoval: false)]
    protected Collection $groupGroupUsers;


    public function __construct()
    {
        $this->groupGroupProjects = new ArrayCollection();
        $this->groupGroupUsers = new ArrayCollection();
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

    public function setParent(?Group $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?Group
    {
        return $this->parent;
    }

    /**
     * Get GroupProjects that are accessible via the many-to-many relationship
     * @return Collection<GroupProject>
     */
    public function getGroupProjects(): Collection
    {
        return $this->groupGroupProjects;
    }

    public function addGroupGroupProject(GroupProject $groupProject): Group
    {
        $this->groupGroupProjects->add($groupProject);

        return $this;
    }

    /**
     * Get GroupUsers that are accessible via the many-to-many relationship
     * @return Collection<GroupUser>
     */
    public function getGroupUsers(): Collection
    {
        return $this->groupGroupUsers;
    }

    public function addGroupGroupUser(GroupUser $groupUser): Group
    {
        $this->groupGroupUsers->add($groupUser);

        return $this;
    }


    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    #[Ignore]
    public function getCopy(?Group $clone = null): Group
    {
        if ($clone === null) {
            $clone = new Group();
        }
        $clone->setName($this->name);

        $clone->setParent($this->parent);
// #BlockStart number=254 id=_19_0_3_40d01a2_1646802256748_639463_4967_#_2

// #BlockEnd number=254

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

// #BlockStart number=255 id=_19_0_3_40d01a2_1646802256748_639463_4967_#_3
//        to remove constraint use following code
//        unset($metadata->properties['PROPERTY']);
//        unset($metadata->members['PROPERTY']);
// #BlockEnd number=255
    }

    #[Ignore]
    public function getGeneratedFilterFields(): array
    {
        return [
            "_group.id",
            "_group.name",
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
            "parent",
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
        "groupProjects" => "GroupProject",
        "groupUsers" => "GroupUser",
    ];


    #[Ignore]
    public static array $childProperties = [
    ];

// #BlockStart number=256 id=_19_0_3_40d01a2_1646802256748_639463_4967_#_4

    /**
     * The toString method based on the private __toString autogenerated method
     * If necessary override.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public function getUserModifiableFields(): array
    {
        return [
            'name',
            'parent',
        ];
    }

// #BlockEnd number=256

}
