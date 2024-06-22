<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditValidationType extends AbstractType
{
    /**
     * CompareScoresType constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remarks', TextareaType::class, ['required' => true, 'label' => false])
            ->add('submit', SubmitType::class, [
                'label' => $this->translator->trans('application.general.save_button', [], 'application'),
                'attr' => [
                    'class' => 'btn btn-success edit-validation-submit prevent-double-click'
                ]
            ]);
    }
}
