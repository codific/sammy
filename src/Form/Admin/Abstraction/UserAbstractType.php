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

use App\Entity\User;
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

class UserAbstractType extends AbstractType
{
    /**
     * Build the form
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $emailOptions = [];
        $emailOptions['required'] = true;
        $emailOptions['label'] = 'admin.user.email';
        $this->elements['email'] = ['name' => 'email', 'type' => TextType::class, 'options' => $emailOptions, 'order' => 10];

        $nameOptions = [];
        $nameOptions['required'] = false;
        $nameOptions['label'] = 'admin.user.name';
        $this->elements['name'] = ['name' => 'name', 'type' => TextType::class, 'options' => $nameOptions, 'order' => 20];

        $surnameOptions = [];
        $surnameOptions['required'] = false;
        $surnameOptions['label'] = 'admin.user.surname';
        $this->elements['surname'] = ['name' => 'surname', 'type' => TextType::class, 'options' => $surnameOptions, 'order' => 30];
    }

    /**
     * Configure form options
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
