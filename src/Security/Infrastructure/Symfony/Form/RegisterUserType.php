<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Security\Infrastructure\Symfony\Type\RoleType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegisterUserType extends AbstractType
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
            ->add('account', EntityType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => true,
                'class' => Account::class
            ])
            ->add('roles', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'required' => false,
                'multiple' => true,
                'expanded' => true
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;

        parent::buildForm($builder, $options);
    }
}