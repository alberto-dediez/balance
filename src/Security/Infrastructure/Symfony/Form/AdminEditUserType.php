<?php

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AdminEditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'choices' => Role::casesForAdmins(),
                'required' => false,
                'multiple' => true
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar')
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir')
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EditUser::class
        ]);
    }
}