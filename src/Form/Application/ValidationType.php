<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Enum\ValidationStatus;
use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidationType extends AbstractType
{
    public const SAVE_BUTTON = 'SAVE';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remarks', TextareaType::class, ['required' => true, 'label' => false])
            ->add(ValidationStatus::REJECTED->label(), SubmitType::class, [
                'label' => 'Reject',
                'attr' => [
                    'class' => 'validation-reject btn-danger prevent-double-click',
                ],
            ])
            ->add(self::SAVE_BUTTON, SubmitType::class, [
                'label' => 'Save',
                'attr' => [
                    'data-toggle' => 'tooltip',
                    'data-title' => $this->translator->trans('application.assessment.save_validation_remark_tooltip', [], 'application'),
                    'class' => 'validation-save btn-warning prevent-double-click',
                ],
            ])
            ->add(ValidationStatus::ACCEPTED->label(), SubmitType::class, [
                'label' => 'Accept',
                'attr' => [
                    'class' => 'validation-accept btn-success prevent-double-click',
                ],
            ]);
    }
}
