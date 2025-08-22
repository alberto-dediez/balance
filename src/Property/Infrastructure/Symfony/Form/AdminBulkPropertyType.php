<?php

namespace App\Property\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\AssignAccount;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\RemoveProperty;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\BulkPropertyRequest;

class AdminBulkPropertyType extends BulkType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('accountId', AccountIdType::class, [
                'required' => false,
                'placeholder' => new TranslatableMessage('Cuenta'),
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-condition-value' => 'assign-account'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions' => [
                new AssignAccount(),
                new RemoveProperty(),
            ],
            'class' => BulkPropertyRequest::class
        ]);
    }
}