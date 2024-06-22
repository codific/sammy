<?php

declare(strict_types=1);

namespace App\Form\Application;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'email',
            EmailType::class,
            ['constraints' => [new NotBlank(), new Email(['mode' => 'strict'])]]
        );
    }
}
