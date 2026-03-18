<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Form\Type\AccountType;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class AdminListUsersType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => new TranslatableMessage('Nombre de usuario'),
                'required' => false,
            ])
            ->add('account', AccountType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'placeholder' => new TranslatableMessage('Todas las cuentas'),
                'required' => false,
            ])
            ->add('role', RoleType::class, [
                'label' => new TranslatableMessage('Rol de usuario'),
                'placeholder' => new TranslatableMessage('Todos los roles de usuario'),
                'choices' => Role::casesForAdmins(),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListUsersRequest::class,
        ]);
    }
}
