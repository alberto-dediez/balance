<?php

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\ListUsers\ListUsersRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Security\Infrastructure\Symfony\Type\RoleType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
            ->add('limit', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('orderBy', HiddenType::class)
            ->add('orderWay', HiddenType::class)
            ->add('reset', SubmitType::class, [
                'label' => new TranslatableMessage('Resetear filtro')
            ])
            ->add('filter', SubmitType::class, [
                'label' => new TranslatableMessage('Filtrar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListUsersRequest::class
        ]);
    }
}