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

interface PasswordResetInterface
{
    /**
     * @return mixed
     */
    public function setPasswordResetHash(?string $passwordResetHash);

    public function getPasswordResetHash(): ?string;

    /**
     * @return mixed
     */
    public function setPasswordResetHashExpiration(?\DateTime $passwordResetHashExpiration);

    public function getPasswordResetHashExpiration(): ?\DateTime;
}
