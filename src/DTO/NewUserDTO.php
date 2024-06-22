<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;
use App\Enum\Role;
use App\Validator\UniqueUser;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueUser]
final class NewUserDTO
{
    #[Assert\NotBlank]
    public string $name;

    #[Assert\NotBlank]
    public string $surname;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    public array $roles;

    public ?string $externalId = '0';

//    TODO: Use this to decide how to welcome the user (with or without pass reset)
//    public int $welcomeMailType = \App\Enum\MailTemplateType::USER_WELCOME;

    public function getEntity(): User
    {
        $user = new User();
        $user->setEmail($this->email);
        $user->setName($this->name);
        $user->setSurname($this->surname);
        $user->setExternalId($this->externalId);
        $roles = $this->roles ?? [Role::USER->string()];
        $roles = array_merge($roles, array_diff([Role::USER->string()], $roles));
        $user->setRoles($roles);

        return $user;
    }

    public function populateFromUser(User $user): NewUserDTO
    {
        $this->email = $user->getEmail();
        $this->name = $user->getName();
        $this->surname = $user->getSurname();
        $this->externalId = $user->getExternalId();
        $this->roles = $user->getRoles();

        return $this;
    }

    public function __toString(): string
    {
        return $this->name." ".$this->surname." (".$this->email.")";
    }
}
