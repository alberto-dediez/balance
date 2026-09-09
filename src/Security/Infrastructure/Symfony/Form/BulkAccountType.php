<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\Type\ConsultantAccountChoiceType;
use App\Security\Infrastructure\UI\Web\Admin\Account\Bulk\AssignConsultant;
use App\Security\Infrastructure\UI\Web\Admin\Account\Bulk\BulkAccountRequest;
use App\Security\Infrastructure\UI\Web\Admin\Account\Bulk\RemoveAccount;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class BulkAccountType extends BulkType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('consultantAccountId', ConsultantAccountChoiceType::class, [
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin consultora'),
                'attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="action"]',
                    'data-showable-container-value' => 'div:not(.input-group)',
                    'data-showable-conditions-value' => json_encode(['assign-consultant']),
                ],
            ])
        ;

        $builder->get('consultantAccountId')->addModelTransformer(
            new AggregateRootIdTransformer(AccountId::class),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions' => [
                new RemoveAccount(),
                new AssignConsultant(),
            ],
            'data_class' => BulkAccountRequest::class,
        ]);
    }
}
