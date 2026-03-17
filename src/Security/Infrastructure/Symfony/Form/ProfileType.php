<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * @extends AbstractType<mixed>
 */
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Contraseña antigua',
                'required' => true,
                'mapped' => false
            ])
            ->add('password', RepeatedType::class, [
                'first_options'  => [
                    'label' => 'Contraseña nueva',
                ],
                'second_options' => [
                    'label' => 'Repetir Contraseña'
                ],
                'type' => PasswordType::class,
                'invalid_message' => 'Las contraseñas no coinciden.',
                'required' => true,
                'mapped' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar')
            ])
        ;
    }
}
