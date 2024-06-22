<?php
/**
 * This is automatically generated file using the Codific Framework generator
 * PHP version 8.
 *
 * @category PHP
 *
 * @author   CODIFIC <info@codific.com>
 *
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Form\Admin;

// #BlockStart number=28 id=_19_0_3_40d01a2_1635863991426_510700_5593_#_0

use App\Form\Admin\Abstraction\ProjectAbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends ProjectAbstractType
{
    public const GROUPS = 'groups';

    /**
     * Build the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        unset($this->elements['assessment']);

        $this->elements['validationThreshold']['type'] = NumberType::class;

        $groupOptions = [];
        $groupOptions['required'] = false;
        $groupOptions['label'] = 'application.project.teams';
        $groupOptions['placeholder'] = false;
        $groupOptions['mapped'] = false;
        $groupOptions['expanded'] = true;
        $groupOptions['multiple'] = true;
//        $groupOptions['choice_attr'] = function () {
//            return ['checked' => true];
//        };
        $groupOptions['choice_loader'] = new CallbackChoiceLoader(function () use ($options) {
            return array_flip($options[self::GROUPS]);
        });
        $this->elements['groups'] = ['name' => self::GROUPS, 'type' => ChoiceType::class, 'options' => $groupOptions, 'order' => 30];

        $this->useApplicationTranslations();
        $this->elements['validationThreshold']['options']['help'] = 'application.project.validation_threshold_tooltip';

        $this->addElements($builder);
    }

    /**
     * Configure form options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(self::GROUPS, []);
        parent::configureOptions($resolver);
    }

    private function useApplicationTranslations(): void
    {
        foreach ($this->elements as &$element) {
            $currentOptions = $element['options'];
            $currentOptions['label'] = str_replace('admin', 'application', $currentOptions['label']);
            $element['options'] = $currentOptions;
        }
    }
}

// #BlockEnd number=28
