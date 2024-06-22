<?php
/**
 * This is automatically generated file using the BOZA Framework generator.
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

namespace App\Form\Application;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'oldPassword',
            PasswordType::class,
            [
                'label' => $this->translator->trans('application.general.old_password', [], 'application'),
                'translation_domain' => 'application',
                'constraints' => [
                    new UserPassword(),
                ],
            ],
        );

        $builder->add(
            'newPassword',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'constraints' => [
                    new NotCompromisedPassword(['skipOnError' => true]),
                    new NotBlank(),
                    new Length(
                        [
                            'min' => 12,
                            'max' => 128,
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/[A-Z]/',
                            'match' => true,
                            'message' => $this->translator->trans('application.general.change_password_upper_case_message', [], 'application'),
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/[a-z]/',
                            'match' => true,
                            'message' => $this->translator->trans('application.general.change_password_lower_case_message', [], 'application'),
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/[0-9]/',
                            'match' => true,
                            'message' => $this->translator->trans('application.general.change_password_digit_message', [], 'application'),
                        ]
                    ),
                ],
                'first_options' => [
                    'label' => $this->translator->trans('application.general.new_password', [], 'application'),
                    'translation_domain' => 'application',
                ],
                'second_options' => [
                    'label' => $this->translator->trans('application.general.confirm_password', [], 'application'),
                    'translation_domain' => 'application',
                ],
            ]
        );
    }
}
