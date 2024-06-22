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

// #BlockStart number=43 id=_19_0_3_40d01a2_1635864059122_214388_5671_#_0

use App\Entity\User;
use App\Enum\Role;
use App\Form\Admin\Abstraction\UserAbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends UserAbstractType
{
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
        parent::buildForm($builder, $options);
        $this->addEmailToForm();
        $this->changeRolesElement();

        $defaultRolesExisting = $options['defaultRoles'] !== [];
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $defaultRolesExisting) {
            $user = $event->getData();
            if (!$user instanceof User) {
                throw new \Exception('user is not instance of User');
            }
            if ($defaultRolesExisting) {
                $user->setRoles($options['defaultRoles']);
            }
            $event->setData($user);
        });

        if ($defaultRolesExisting) {
            unset($this->elements['roles']);
        }

        $this->addElements($builder);
    }

    /**
     * Configure form options.
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('defaultRoles', []);
        parent::configureOptions($resolver);
    }

    /**
     * @return void
     */
    private function addEmailToForm()
    {
        $emailOptions = [];
        $emailOptions['required'] = true;
        $emailOptions['label'] = 'admin.user.email';
        $this->elements['email'] = ['name' => 'email', 'type' => TextType::class, 'options' => $emailOptions, 'order' => 20];
    }

    /**
     * @return void
     */
    private function changeRolesElement()
    {
        $roles = [];
        foreach (User::getAllNonAdminRoles() as $role) {
            if ($role === Role::USER->string()) {
                continue;
            }
            $beautifiedString = $this->translator->trans('admin.user.non_admin_role_enum', ['value' => $role]);
            $roles[$beautifiedString] = $role;
        }

        $rolesOptions = [];
        $rolesOptions['choices'] = $roles;
        $rolesOptions['expanded'] = true;
        $rolesOptions['multiple'] = true;
        $rolesOptions['required'] = false;
        $rolesOptions['label'] = 'admin.user.roles';

        $this->elements['roles'] = ['name' => 'roles', 'type' => ChoiceType::class, 'options' => $rolesOptions, 'order' => 100];
    }
}

// #BlockEnd number=43
