<?php

/**
 * This is automatically generated file using the Codific Framework generator
 * PHP version 8
 * @category PHP
 * @author   CODIFIC <info@codific.com>
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Form\Admin\Abstraction;

use App\Entity\Group;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class GroupAbstractType extends AbstractType
{
    /**
     * Build the form
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nameOptions = [];
        $nameOptions['required'] = true;
        $nameOptions['label'] = 'admin.group.name';
        $this->elements['name'] = ['name' => 'name', 'type' => TextType::class, 'options' => $nameOptions, 'order' => 10];

        $parentOptions = [];
        $parentOptions['required'] = false;
        $parentOptions['class'] = Group::class;
        $parentOptions['attr']['class'] = 'select2';
        $parentOptions['label'] = 'admin.group.parent';
        if (count($options['parents']) > 0) {
            $parentOptions['choices'] = $options['parents'];
        }
        $parentOptions['placeholder'] = 'admin.group.select_placeholder';
        $parentOptions['placeholder_translation_parameters'] = ['name' => 'parent'];
        $this->elements['parent'] = ['name' => 'parent', 'type' => EntityType::class, 'options' => $parentOptions, 'order' => 20];
    }

    /**
     * Configure form options
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            "parents" => [],
        ]);
    }
}
