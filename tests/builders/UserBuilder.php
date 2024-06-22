<?php

namespace App\Tests\builders;

use App\Entity\User;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;

class UserBuilder
{
    private ?EntityManagerInterface $entityManager;
    private ?string $email;
    private array $roles;
    private ?string $secretKey;
    private bool $agreedToTerms;
    private ?string $name;
    private ?string $surname;
    private ?string $password;
    private ?string $passwordResetHash;
    private ?\DateTime $passwordResetHashExpiration;
    private ?string $externalId;

    public function __construct(EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    public function withEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function withRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function withSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function withAgreedToTerms(bool $agreedToTerms): self
    {
        $this->agreedToTerms = $agreedToTerms;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function withPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function withPasswordResetHash(string $passwordResetHash): self
    {
        $this->passwordResetHash = $passwordResetHash;

        return $this;
    }

    public function withPasswordResetHashExpiration(\DateTime $passwordResetHashExpiration): self
    {
        $this->passwordResetHashExpiration = $passwordResetHashExpiration;

        return $this;
    }

    public function withExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function build(bool $persist = true): User
    {
        $user = new User();
        $user->setEmail($this->email ?? "email_".bin2hex(random_bytes(5))."@test.test");
        $user->setRoles($this->roles ?? [Role::USER->string()]);
        $user->setSecretKey($this->secretKey ?? "MFZWIZDEMRSQ====");
        $user->setAgreedToTerms($this->agreedToTerms ?? true);
        $user->setName($this->name ?? 'Manol');
        $user->setSurname($this->surname ?? "Manolov");
        $user->setPassword($this->password ?? '$2a$12$yNaZmq0qLazJUMsiIbsO6eXW9v2uYilotB0uNFclWTywNOrpZLa9e'); // admin123
        $user->setPasswordResetHash($this->passwordResetHash ?? null);
        $user->setPasswordResetHashExpiration($this->passwordResetHashExpiration ?? null);
        $user->setExternalId($this->externalId ?? null);

        if ($persist && $this->entityManager !== null) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }
}