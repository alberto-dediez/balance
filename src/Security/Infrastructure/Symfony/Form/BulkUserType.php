<?php

namespace App\Security\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Security\Infrastructure\UI\Web\User\Bulk\RemoveUser;
use App\Security\Infrastructure\UI\Web\User\Bulk\BulkUserRequest;

class BulkUserType extends BulkType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', RoleType::class, [
                'required' => true,
                'placeholder' => new TranslatableMessage('Rol de usuario'),
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-conditions-value' => json_encode(['grant-role', 'ungrant-role'])
                ]
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions' => [
                new RemoveUser(),
            ],
            'data_class' => BulkUserRequest::class
        ]);
    }
}
