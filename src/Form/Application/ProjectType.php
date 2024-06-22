<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Metamodel;
use App\Form\Admin\Abstraction\ProjectAbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
        unset($this->elements['template']);
        unset($this->elements['templateProject']);
        $this->elements['validationThreshold']['type'] = NumberType::class;

        $metamodelOptions = [];
        $metamodelOptions['required'] = true;
        $metamodelOptions['class'] = Metamodel::class;
        $metamodelOptions['label'] = 'application.project.metamodel';
        if (count($options['metamodels']) > 0) {
            $metamodelOptions['choices'] = $options['metamodels'];
        }
        $metamodelOptions['placeholder'] = false;
        $this->elements['metamodel'] = ['name' => 'metamodel', 'type' => EntityType::class, 'options' => $metamodelOptions, 'order' => -10];

        $groupOptions = [];
        $groupOptions['required'] = false;
        $groupOptions['label'] = 'application.project.teams';
        $groupOptions['placeholder'] = false;
        $groupOptions['mapped'] = false;
        $groupOptions['expanded'] = true;
        $groupOptions['multiple'] = true;
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
        $resolver->setDefault('metamodels', []);
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
