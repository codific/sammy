<?php

declare(strict_types=1);

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueUser) {
            throw new UnexpectedTypeException($constraint, UniqueUser::class);
        }
        $existingUser = $this->userRepository->findBy(
            [
                'email' => $value->email ?? '',
                'externalId' => $value->externalId ?? '0',
                'deletedAt' => null,
            ]
        );
        if (count($existingUser) !== 0) {
            $this->context->buildViolation($constraint->message)->atPath('email')->addViolation();
        }
    }
}
