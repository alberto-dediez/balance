<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\User\Export\UsersExportFilter;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\DataExchange\UsersExportationType;
use App\Security\Infrastructure\Symfony\Form\Type\AccountType;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<UsersExportFilter> */
class AdminUsersExportType extends AbstractType
{
    public function __construct(private readonly UsersExportationType $usersExportationType) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $canExportEveryAccount = $this->usersExportationType->canExportEveryAccount();

        if ($canExportEveryAccount) {
            $builder->add('account', AccountType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'placeholder' => new TranslatableMessage('Todas las cuentas'),
                'required' => false,
            ]);
        }

        $builder->add('role', RoleType::class, [
            'label' => new TranslatableMessage('Rol de usuario'),
            'placeholder' => new TranslatableMessage('Todos los roles de usuario'),
            'choices' => $canExportEveryAccount
                ? Role::casesForAdmins()
                : Role::casesForOwners() + [Role::Provider->value => Role::Provider],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UsersExportFilter::class,
        ]);
    }
}
