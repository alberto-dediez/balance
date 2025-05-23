<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\List\ListUsersRequest;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;

class ListUsersType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => new TranslatableMessage('Nombre de usuario'),
                'required' => false
            ])
            ->add('role', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'placeholder' => new TranslatableMessage('Todos los roles de usuario'),
                'required' => false
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListUsersRequest::class
        ]);
    }
}