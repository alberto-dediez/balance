<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\AssignAccount;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\AssignDistributor;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\BulkPropertyRequest;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\RemoveProperty;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Security\Infrastructure\Symfony\Form\Type\DistributorAccountChoiceType;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

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
                    'data-showable-condition-value' => 'assign-account',
                ],
            ])
            ->add('distributorAccountId', DistributorAccountChoiceType::class, [
                'required' => false,
                'placeholder' => new TranslatableMessage('Distribuidor'),
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-condition-value' => 'assign-distributor',
                ],
            ])
        ;

        $builder->get('distributorAccountId')->addModelTransformer(
            new AggregateRootIdTransformer(AccountId::class),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions' => [
                new AssignAccount(),
                new AssignDistributor(),
                new RemoveProperty(),
            ],
            'data_class' => BulkPropertyRequest::class,
        ]);
    }
}
