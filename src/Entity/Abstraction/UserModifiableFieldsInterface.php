<?php

declare(strict_types=1);

namespace App\Entity\Abstraction;

interface UserModifiableFieldsInterface
{
    public function getUserModifiableFields(): array;
}
