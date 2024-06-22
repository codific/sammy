<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class, EntityType::class];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['enum_translation', 'placeholder_translation_parameters']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $enumTranslation = null;
        $placeholderTranslationParameters = [];
        if (isset($options['enum_translation'])) {
            $enumTranslation = $options['enum_translation'];
        }
        if (isset($options['placeholder_translation_parameters'])) {
            $placeholderTranslationParameters = $options['placeholder_translation_parameters'];
        }
        $view->vars['enum_translation'] = $enumTranslation;
        $view->vars['placeholder_translation_parameters'] = $placeholderTranslationParameters;
    }
}
