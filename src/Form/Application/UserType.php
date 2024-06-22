<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Group;
use App\Entity\User;
use App\Enum\Role;
use App\Form\Admin\Abstraction\AbstractType;
use App\Utils\DateTimeUtil;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    public const DISABLE_EMAIL = 'disableEmail';
    public const DISABLE_ROLES = 'disableRoles';
    public const DISABLE_TEAMS = 'disableTeams';
    public const SHOW_TIMEZONE_SUPPORT = 'timeZoneSupport';

    /**
     * UserType constructor.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly DateTimeUtil $dateTimeUtil
    ) {
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

        $user = $options['data'];
        $shouldDisableEmail = ($user !== null && (bool)$user->getEmail()) || $options[self::DISABLE_EMAIL] === true;
        $builder->add('email', EmailType::class, [
            'required' => !$shouldDisableEmail,
            'disabled' => $shouldDisableEmail,
            'label' => 'application.user.email',
        ]);

        $builder->add('roles', ChoiceType::class, [
            'required' => $options[self::DISABLE_ROLES] === false,
            'disabled' => $options[self::DISABLE_ROLES] === true,
            'label' => 'application.user.roles',
            'help' => (in_array(Role::MANAGER->string(), $user->getRoles(), true)) ? $this->translator->trans('application.user.roles_change_warning', [], 'application') : '',
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
            'data' => $options[self::DISABLE_TEAMS] === true ? $this->getGroups($options['groups']) : null,
            'expanded' => true,
            'multiple' => true,
        ]);

        if ($options[self::SHOW_TIMEZONE_SUPPORT] === true) {
            $builder->add(
                'dateFormat',
                ChoiceType::class,
                [
                    'label' => 'application.user.date_format',
                    'choices' => array_flip($this->dateTimeUtil->getAvailableDateFormats()),
                ]
            );

            $builder->add(
                'timeZone',
                ChoiceType::class,
                [
                    'label' => 'application.user.time_zone',
                    'choices' => $this->getTimeZoneChoices($builder->getData()->getTimeZone()),
                ],
            );
        }
    }

    /**
     * @return int[]|string[]
     */
    private function getTimeZoneChoices(?string $userTimezone): array
    {
        $choices = $this->dateTimeUtil->getAvailableTimeZones();
        if ($userTimezone !== null && $userTimezone !== '' && !array_key_exists($userTimezone, $choices)) {
            $result = [];
            $result[$userTimezone] = "({$this->dateTimeUtil->getStandardOffsetUTC($userTimezone)}) {$userTimezone}";
            $choices = $result + $choices;
        }

        return array_flip($choices);
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
        $resolver->setDefault('selectedGroup', null);
        parent::configureOptions($resolver);
    }
}
