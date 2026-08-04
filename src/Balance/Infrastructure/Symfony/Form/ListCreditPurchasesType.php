<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesRequest;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Symfony\Form\Type\CreditPurchaseStatusType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListCreditPurchasesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('projectId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Proyecto'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
                'choices' => $options['projects'],
                'choice_value' => static fn(?ProjectId $projectId): ?string => $projectId?->id(),
            ])
            ->add('status', CreditPurchaseStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('reference', TextType::class, [
                'label' => new TranslatableMessage('Identificador'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListCreditPurchasesRequest::class,
            'projects' => [],
        ]);

        $resolver->setAllowedTypes('projects', 'array');
    }
}
