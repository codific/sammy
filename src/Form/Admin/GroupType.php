<?php
/**
 * This is automatically generated file using the Codific Framework generator
 * PHP version 8
 * @category PHP
 * @author   CODIFIC <info@codific.com>
 * @see     http://codific.com
 */

declare(strict_types=1);

namespace App\Form\Admin;

// #BlockStart number=267 id=_19_0_3_40d01a2_1646802256748_639463_4967_#_0

use App\Form\Admin\Abstraction\GroupAbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends GroupAbstractType
{
    public const USERS = 'users';

    /**
     * Build the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        unset($this->elements['parent']);

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
        $resolver->setDefault(self::USERS, []);
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

// #BlockEnd number=267
