<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Group;
use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupFilterType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('group', EntityType::class, [
            'class' => Group::class,
            'label' => $this->translator->trans('application.general.team', [], 'application'),
            'required' => false,
            'choices' => $options['groups'],
            'data' => $options['data']['selected'],
            'placeholder' => $this->translator->trans('application.user.all_users', [], 'application'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'groups' => [],
        ]);
        parent::configureOptions($resolver);
    }
}
