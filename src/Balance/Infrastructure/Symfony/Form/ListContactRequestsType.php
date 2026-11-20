<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\ContactRequest\List\ListContactRequestsRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListContactRequestsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resolved', BooleanType::class, [
                'label' => new TranslatableMessage('Terminada'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
            ])
            ->add('accountId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas'),
                'choices' => $options['accounts'],
                'choice_value' => static fn(?AccountId $accountId): ?string => $accountId?->id(),
            ])
            ->add('search', TextType::class, [
                'label' => new TranslatableMessage('Nombre o email'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListContactRequestsRequest::class,
            'accounts' => [],
        ]);

        $resolver->setAllowedTypes('accounts', 'array');
    }
}
