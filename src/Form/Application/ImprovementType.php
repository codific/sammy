<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImprovementType extends AbstractType
{
    public const CHOSEN_DATE = 'chosenDate';
    public const WRITTEN_PLAN = 'writtenPlan';
    public const SHOW_SAVE_TEMP_PLAN_BUTTON = 'showTempSavePlan';
    public const DATE_FORMAT = 'dateFormat';

    public const SAVE_BUTTON = 'SAVE';
    public const SUBMIT_BUTTON = 'SUBMIT';
    public const CANCEL_BUTTON = 'CANCEL';

    /**
     * ImprovementType constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('targetDate', DateType::class, [
                'label' => $this->translator->trans('application.assessment.improvement_target_date', [], 'application'),
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'format' => $this->parseDateFormat($options[self::DATE_FORMAT] ?? ''),
                'data' => $options[self::CHOSEN_DATE] ?? new \DateTime('+2 months'),
                'attr' => ['class' => 'custom-js-datepicker'],
            ])
            ->add('plan', TextareaType::class, [
                'label' => $this->translator->trans('application.assessment.improvement_plan', [], 'application'),
                'required' => true,
                'data' => $options[self::WRITTEN_PLAN],
                'attr' => ['class' => 'improvement-plan-area'],
            ])
            ->add('newDesiredAnswers', HiddenType::class, [
                'required' => false,
                'data' => '{}',
            ])
            ->add(self::CANCEL_BUTTON, SubmitType::class, [
                'label' => $this->translator->trans('application.assessment.cancel_improvement_button', [], 'application'),
                'attr' => ['class' => 'btn-danger prevent-double-click', 'data-submit-type' => 'cancel'],
            ])
            ->add(self::SUBMIT_BUTTON, SubmitType::class, [
                'label' => $this->translator->trans('application.assessment.start_improvement_button', [], 'application'),
                'attr' => ['class' => 'finalize-submit-improvement btn-success prevent-double-click', 'data-submit-type' => 'submit', 'disabled' => 'disabled'],
            ]);

        if ($options[self::SHOW_SAVE_TEMP_PLAN_BUTTON] === true) {
            $builder->add(self::SAVE_BUTTON, SubmitType::class, [
                'label' => $this->translator->trans('application.assessment.save_improvement_button', [], 'application'),
                'attr' => [
                    'class' => 'btn-warning prevent-double-click',
                    'data-toggle' => 'tooltip',
                    'data-title' => $this->translator->trans('application.assessment.save_improvement_button_tooltip', [], 'application'),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            self::CHOSEN_DATE => null,
            self::WRITTEN_PLAN => null,
            self::SHOW_SAVE_TEMP_PLAN_BUTTON => false,
            self::DATE_FORMAT => null,
        ]);
    }

    private function parseDateFormat(string $format)
    {
        return match ($format) {
            'd-m-Y' => 'dd-MM-yy',
            'm-d-Y' => 'MM-dd-yy',
            'Y-m-d' => 'yy-MM-dd',
            default => 'dd-MM-yyyy'
        };
    }
}
