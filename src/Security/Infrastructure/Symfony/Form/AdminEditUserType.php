<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<EditUser>
 */
class AdminEditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'choices' => Role::casesForAdmins(),
                'required' => false,
                'multiple' => true,
            ])
            ->add('preferredLocale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma preferido'),
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EditUser::class,
        ]);
    }
}
