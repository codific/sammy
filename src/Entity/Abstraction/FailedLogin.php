<?php
/**
 * This is automatically generated file using the Codific Prototizer.
 *
 * PHP version 8
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Entity\Abstraction;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: '`failedlogin`')]
#[ORM\Entity(repositoryClass: "App\Repository\Abstraction\FailedLoginRepository")]
#[ORM\HasLifecycleCallbacks]
class FailedLogin extends AbstractEntity
{
    #[ORM\Column(name: 'username', type: Types::STRING, nullable: true)]
    private ?string $username = '';

    #[ORM\Column(name: 'ip', type: Types::STRING, nullable: true)]
    private string $ip = '';

    public function __toString(): string
    {
        return '';
    }

    public function setUsername(?string $username): FailedLogin
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setIp(string $ip): FailedLogin
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }
}
