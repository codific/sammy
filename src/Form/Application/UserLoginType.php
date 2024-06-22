<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'email',
            EmailType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 5]),
                ],
            ]
        );
    }
}
