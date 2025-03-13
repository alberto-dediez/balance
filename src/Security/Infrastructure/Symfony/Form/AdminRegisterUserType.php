<?php

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Security\Infrastructure\Symfony\Type\RoleType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Security\Infrastructure\Symfony\Type\AccountType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class AdminRegisterUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => new TranslatableMessage('Nombre de usuario'),
                'required' => true
            ])
            ->add('password', PasswordType::class, [
                'label' => new TranslatableMessage('Contraseña'),
                'required' => true
            ])
            ->add('account', AccountType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => true,
            ])
            ->add('roles', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'choices' => Role::casesForAdmins(),
                'required' => false,
                'multiple' => true
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;

        parent::buildForm($builder, $options);
    }
}