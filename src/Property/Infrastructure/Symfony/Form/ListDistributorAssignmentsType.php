<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Security\Infrastructure\Symfony\Form\Type\DistributorAccountChoiceType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListDistributorAssignmentsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('distributorAccountId', DistributorAccountChoiceType::class, [
                'label' => new TranslatableMessage('Distribuidor'),
                'placeholder' => new TranslatableMessage('Distribuidor'),
                'required' => false,
            ])
            ->add('accountId', AccountIdType::class, [
                'label' => new TranslatableMessage('Cuenta adherida'),
                'placeholder' => new TranslatableMessage('Todas las cuentas'),
                'required' => false,
            ])
        ;

        $builder->get('distributorAccountId')->addModelTransformer(
            new AggregateRootIdTransformer(AccountId::class),
        );

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListDistributorAssignmentsRequest::class,
        ]);
    }
}
