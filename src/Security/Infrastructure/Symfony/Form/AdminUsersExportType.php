<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Form\Type\AccountType;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\Export\UsersExportFilter;

class AdminUsersExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UsersExportFilter::class,
        ]);
    }
}
