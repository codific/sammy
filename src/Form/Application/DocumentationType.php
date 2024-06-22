<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Project;
use App\Enum\Custom\RemarkType;
use App\Form\Admin\Abstraction\AbstractType;
use App\Utils\Constants;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentationType extends AbstractType
{
    public const MATURITY_LEVEL_1_VALUE = 1;
    public const MATURITY_LEVEL_2_VALUE = 2;
    public const MATURITY_LEVEL_3_VALUE = 3;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('text', TextareaType::class, [
            'required' => true,
            'label' => false
        ]);
        $builder->add('assessmentStream', HiddenType::class, ['required' => true, 'label' => false, 'mapped' => false]);

        if ($options['project']->getMetamodel()->getId() === Constants::SAMM_ID) {
            $builder->add('maturityLevel', ChoiceType::class, [
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => ['L1' => self::MATURITY_LEVEL_1_VALUE, 'L2' => self::MATURITY_LEVEL_2_VALUE, 'L3' => self::MATURITY_LEVEL_3_VALUE],
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],
            ]);
        }

        $builder->add('remarkId', HiddenType::class, ['required' => false, 'label' => false]);
        $builder->add('remarkType', HiddenType::class, ['required' => false, 'label' => false]);
        $builder->get('remarkType')
            ->addModelTransformer(
                new CallbackTransformer(
                    function (?RemarkType $remarkType): string {
                        return $remarkType?->label() ?? '';
                    },
                    function (?string $remarkLabel = ''): RemarkType {
                        return RemarkType::fromLabel($remarkLabel ?? '');
                    }
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'project' => null,
        ]);

        $resolver->setAllowedTypes('project', Project::class);
    }
}
