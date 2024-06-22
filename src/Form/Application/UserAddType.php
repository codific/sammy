<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Group;
use App\Entity\User;
use App\Enum\Role;
use App\Form\Admin\Abstraction\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserAddType extends AbstractType
{
    public const DISABLE_EMAIL = 'disableEmail';
    public const DISABLE_ROLES = 'disableRoles';
    public const DISABLE_TEAMS = 'disableTeams';
    public const SHOW_TIMEZONE_SUPPORT = 'timeZoneSupport';

    /**
     * UserType constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Build the form.
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'required' => true,
            'label' => 'application.user.name',
        ]);

        $builder->add('surname', TextType::class, [
            'required' => true,
            'label' => 'application.user.surname',
        ]);

        $builder->add('email', EmailType::class, [
            'required' => true,
            'label' => 'application.user.email',
        ]);

        $rolesTranslation = $this->translator->trans("application.user.roles", [], "application");
        $rolesLegendTranslation = $this->translator->trans("application.user.role_descriptions", [], "application");

        $builder->add('roles', ChoiceType::class, [
            'required' => $options[self::DISABLE_ROLES] === false,
            'disabled' => $options[self::DISABLE_ROLES] === true,
            'label' => $rolesTranslation . " <i class=\"fa fa-question-circle\" data-toggle=\"tooltip\" data-html=\"true\" title=\"" . $rolesLegendTranslation . "\"></i> ",
            'label_html' => true,
            'choices' => $this->getRoles(),
            'expanded' => true,
            'multiple' => true,
        ]);

        $builder->add('groups', ChoiceType::class, [
            'mapped' => false,
            'required' => $options[self::DISABLE_TEAMS] === false,
            'disabled' => $options[self::DISABLE_TEAMS] === true,
            'label' => 'application.user.groups',
            'choices' => $this->getGroups($options['groups']),
            'data' => $options[self::DISABLE_TEAMS] === true ? $this->getGroups($options['groups']) : $this->getGroups($options['selectedGroups']),
            'expanded' => true,
            'multiple' => true,
        ]);
    }

    private function getRoles(): array
    {
        $roles = [];
        foreach (User::getAllNonAdminRoles() as $role) {
            if ($role === Role::USER->string()) {
                continue;
            }
            $beautifiedString = $this->translator->trans('application.user.non_admin_role_enum', ['value' => $role], 'application');
            $roles[$beautifiedString] = $role;
        }

        return $roles;
    }

    /**
     * @param Group[] $groups
     */
    private function getGroups(array $groups): array
    {
        $result = [];
        foreach ($groups as $group) {
            $result[$group->getName()] = $group->getId();
        }

        return $result;
    }

    /**
     * Configure form options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(self::DISABLE_EMAIL, false);
        $resolver->setDefault(self::DISABLE_ROLES, false);
        $resolver->setDefault(self::DISABLE_TEAMS, false);
        $resolver->setDefault(self::SHOW_TIMEZONE_SUPPORT, false);
        $resolver->setDefault('groups', []);
        $resolver->setDefault('selectedGroups', []);
        parent::configureOptions($resolver);
    }
}
