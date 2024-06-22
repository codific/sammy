<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class UniqueUser extends Constraint
{
    public string $message = 'general.non_unique_user';

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
