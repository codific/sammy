<?php

declare(strict_types=1);

namespace App\Form\Application;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ToolboxType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('toolboxFile', FileType::class, [
                'attr' => [
                    'placeholder' => $this->translator->trans('application.project.import_toolbox_placeholder', [], 'application'),
                ],
                'constraints' => new \Symfony\Component\Validator\Constraints\File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ],
                    'mimeTypesMessage' => $this->translator->trans('application.project.import_toolbox_type_error', [], 'application'),
                ]),
            ])
        ->add('autoValidate', CheckboxType::class, [
            'label' => $this->translator->trans('application.project.import_toolbox_autovalidate', [], 'application'),
            'data' => true,
            'required' => false,
        ]);
    }
}
