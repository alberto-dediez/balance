<?php

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Form\FormBuilderInterface;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Security\Infrastructure\UI\Web\User\Bulk\GrantRole;
use App\Security\Infrastructure\UI\Web\User\Bulk\RemoveUser;
use App\Security\Infrastructure\UI\Web\User\Bulk\UngrantRole;
use App\Security\Infrastructure\UI\Web\User\Bulk\BulkUserRequest;

class AdminBulkUserType extends BulkType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('role', RoleType::class, [
                'required' => true,
                'choices' => Role::casesForAdmins(),
                'placeholder' => new TranslatableMessage('Rol de usuario'),
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-conditions-value' => json_encode(['grant-role', 'ungrant-role'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions' => [
                new RemoveUser(),
                new GrantRole(),
                new UngrantRole()
            ],
            'data_class' => BulkUserRequest::class
        ]);
    }
}
