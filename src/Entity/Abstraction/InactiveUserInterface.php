<?php
/**
 * This is automatically generated file using the Codific Prototizer
 * PHP version 8.
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Entity\Abstraction;

interface InactiveUserInterface
{
    /**
     * Returns the inactive status.
     */
    public function getInactiveStatus(): int;

    /**
     * Sets the inactive status.
     */
    public function setInactiveStatus(int $status): self;
}
