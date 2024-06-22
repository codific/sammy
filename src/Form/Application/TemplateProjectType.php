<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Metamodel;
use App\Form\Admin\Abstraction\ProjectAbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateProjectType extends ProjectAbstractType
{
    /**
     * Build the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        unset($this->elements['assessment']);
        unset($this->elements['validationThreshold']);
        unset($this->elements['templateProject']);
        unset($this->elements['template']);

        $metamodelOptions = [];
        $metamodelOptions['required'] = true;
        $metamodelOptions['class'] = Metamodel::class;
        $metamodelOptions['label'] = 'application.project.metamodel';
        if (count($options['metamodels']) > 0) {
            $metamodelOptions['choices'] = $options['metamodels'];
        }
        $metamodelOptions['placeholder'] = false;
        $this->elements['metamodel'] = ['name' => 'metamodel', 'type' => EntityType::class, 'options' => $metamodelOptions, 'order' => -10];

        $this->useApplicationTranslations();

        $this->addElements($builder);
    }

    /**
     * Configure form options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
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
