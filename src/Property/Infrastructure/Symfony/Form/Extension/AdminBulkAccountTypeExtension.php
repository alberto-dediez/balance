<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Extension;

use App\Property\Infrastructure\UI\Web\Admin\DistributorAssignment\Bulk\AssignDistributorToAccount;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\BulkAccountType;
use App\Security\Infrastructure\Symfony\Form\Type\DistributorAccountChoiceType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class AdminBulkAccountTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [BulkAccountType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('distributorAccountId', DistributorAccountChoiceType::class, [
                'required' => false,
                'placeholder' => new TranslatableMessage('Distribuidor'),
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-conditions-value' => json_encode(['assign-distributor']),
                ],
            ])
        ;

        $builder->get('distributorAccountId')->addModelTransformer(
            new AggregateRootIdTransformer(AccountId::class),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->addNormalizer(
            'actions',
            /**
             * @param list<BulkAction> $actions
             *
             * @return list<BulkAction>
             */
            static fn(Options $options, array $actions): array => [...$actions, new AssignDistributorToAccount()],
        );
    }
}
