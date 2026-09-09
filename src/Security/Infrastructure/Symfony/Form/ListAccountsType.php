<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\Type\ConsultantAccountChoiceType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListAccountsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('active', BooleanType::class, [
                'label' => new TranslatableMessage('Activa'),
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'required' => false,
            ])
            ->add('consultant', BooleanType::class, [
                'label' => new TranslatableMessage('Es consultora'),
                'placeholder' => new TranslatableMessage('Consultoras y no consultoras'),
                'required' => false,
            ])
            ->add('consultantAccountId', ConsultantAccountChoiceType::class, [
                'label' => new TranslatableMessage('Consultora'),
                'placeholder' => new TranslatableMessage('Todas las consultoras'),
                'required' => false,
            ])
        ;

        $builder->get('consultantAccountId')->addModelTransformer(
            new AggregateRootIdTransformer(AccountId::class),
        );

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListAccountsRequest::class,
        ]);
    }
}
