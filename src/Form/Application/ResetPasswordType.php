<?php

declare(strict_types=1);

namespace App\Form\Application;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasswordType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ResetPasswordType constructor.
     */
    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                            'message' => $this->translator->trans('application.general.change_password_upper_case_message', domain: 'application'),
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/[a-z]/',
                            'match' => true,
                            'message' => $this->translator->trans('application.general.change_password_lower_case_message', domain: 'application'),
                        ]
                    ),
                    new Regex(
                        [
                            'pattern' => '/[0-9]/',
                            'match' => true,
                            'message' => $this->translator->trans('application.general.change_password_digit_message', domain: 'application'),
                        ]
                    ),
                ],
                'first_options' => [
                    'label' => 'application.general.new_password',
                ],
                'second_options' => [
                    'label' => 'application.general.confirm_password',
                ],
            ]
        );
    }
}
