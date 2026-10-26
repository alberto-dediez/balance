<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\Username;
use App\Security\Infrastructure\Symfony\Form\Type\AccountType;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<RegisterUserRequest>
 */
class AdminRegisterUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => new TranslatableMessage('Nombre de usuario'),
                'required' => true,
                'constraints' => [
                    new Regex(
                        pattern: Username::PATTERN,
                        message: 'El nombre de usuario no puede contener los caracteres < ni >',
                    ),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => new TranslatableMessage('Contraseña'),
                'required' => true,
            ])
            ->add('account', AccountType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => true,
            ])
            ->add('roles', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'choices' => Role::casesForAdmins(),
                'required' => false,
                'multiple' => true,
            ])
            ->add('preferredLocale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma preferido'),
                'required' => false,
                'mapped' => false,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }
}
