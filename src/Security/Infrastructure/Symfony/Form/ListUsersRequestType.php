<?php

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\Account\Account;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Security\Infrastructure\Symfony\Type\RoleType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\ListUsers\ListUsersRequest;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Security\Infrastructure\Symfony\Form\BaseFilterType;

class ListUsersRequestType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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