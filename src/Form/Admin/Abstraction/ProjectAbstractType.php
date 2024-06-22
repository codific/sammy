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

namespace App\Form\Admin\Abstraction;

use App\Entity\Assessment;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectAbstractType extends AbstractType
{
    /**
     * Build the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nameOptions = [];
        $nameOptions['required'] = true;
        $nameOptions['label'] = 'admin.project.name';
        $this->elements['name'] = ['name' => 'name', 'type' => TextType::class, 'options' => $nameOptions, 'order' => 10];

        $validationThresholdOptions = [];
        $validationThresholdOptions['required'] = false;
        $validationThresholdOptions['label'] = 'admin.project.validation_threshold';
        $this->elements['validationThreshold'] = ['name' => 'validationThreshold', 'type' => TextType::class, 'options' => $validationThresholdOptions, 'order' => 20];

        $descriptionOptions = [];
        $descriptionOptions['required'] = false;
        $descriptionOptions['label'] = 'admin.project.description';
        $descriptionOptions['attr']['rows'] = '3';
        $descriptionOptions['attr']['cols'] = '100';
        $this->elements['description'] = ['name' => 'description', 'type' => TextareaType::class, 'options' => $descriptionOptions, 'order' => 30];

        $templateOptions = [];
        $templateOptions['required'] = false;
        $templateOptions['label'] = 'admin.project.template';
        $templateOptions['label_attr']['class'] = 'checkbox-custom';
        $this->elements['template'] = ['name' => 'template', 'type' => CheckboxType::class, 'options' => $templateOptions, 'order' => 40];

        $assessmentOptions = [];
        $assessmentOptions['required'] = false;
        $assessmentOptions['class'] = Assessment::class;
        $assessmentOptions['attr']['class'] = 'select2';
        $assessmentOptions['label'] = 'admin.project.assessment';
        if (count($options['assessments']) > 0) {
            $assessmentOptions['choices'] = $options['assessments'];
        }
        $assessmentOptions['placeholder'] = false;
        $this->elements['assessment'] = ['name' => 'assessment', 'type' => EntityType::class, 'options' => $assessmentOptions, 'order' => 60];

        $templateProjectOptions = [];
        $templateProjectOptions['required'] = false;
        $templateProjectOptions['class'] = Project::class;
        $templateProjectOptions['attr']['class'] = 'select2';
        $templateProjectOptions['label'] = 'admin.project.template_project';
        if (count($options['templateProjects']) > 0) {
            $templateProjectOptions['choices'] = $options['templateProjects'];
        }
        $templateProjectOptions['placeholder'] = 'admin.project.select_placeholder';
        $templateProjectOptions['placeholder_translation_parameters'] = ['name' => 'templateProject'];
        $this->elements['templateProject'] = ['name' => 'templateProject', 'type' => EntityType::class, 'options' => $templateProjectOptions, 'order' => 70];
    }

    /**
     * Configure form options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'assessments' => [],
            'templateProjects' => [],
        ]);
    }
}
