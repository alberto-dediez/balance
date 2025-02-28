<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Security\Infrastructure\Symfony\Type\RoleType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\ListUsers\ListUsersRequest;
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
            ->add('account', EntityType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'placeholder' => new TranslatableMessage('Todas las cuentas'),
                'required' => false,
                'class' => Account::class
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