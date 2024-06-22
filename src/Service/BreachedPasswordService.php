<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validation;

class BreachedPasswordService
{
    public function check(string $password): bool
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($password, [new NotCompromisedPassword(['skipOnError' => true])]);
        if (0 !== count($violations)) {
            return true;
        }

        return false;
    }
}
