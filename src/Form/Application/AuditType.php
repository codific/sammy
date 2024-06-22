<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuditType extends AbstractType
{
    public const SAVE_BUTTON = 'SAVE';
    public const SUBMIT_BUTTON = 'SUBMIT';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remarks', TextareaType::class, ['required' => true, 'label' => false])
            ->add(self::SAVE_BUTTON, SubmitType::class, [
                'label' => 'Save',
                'attr' => [
                    'data-toggle' => 'tooltip',
                    'data-title' => $this->translator->trans('application.assessment.save_validation_remark_tooltip', [], 'application'),
                    'class' => 'audit-save btn-warning prevent-double-click',
                ],
            ])
            ->add(self::SUBMIT_BUTTON, SubmitType::class, [
                'label' => 'Submit',
                'attr' => [
                    'class' => 'audit-accept btn-success prevent-double-click',
                ],
            ]);
    }
}
