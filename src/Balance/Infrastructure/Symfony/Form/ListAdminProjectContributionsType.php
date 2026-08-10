<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** Same filters as the account list plus the account itself, which only the superadmin can choose. */
class ListAdminProjectContributionsType extends ListProjectContributionsType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('accountId', AutocompleteType::class, [
            'label' => new TranslatableMessage('Cuenta'),
            'required' => false,
            'placeholder' => new TranslatableMessage('Todas'),
            'choices' => $options['accounts'],
            'choice_value' => static fn(?AccountId $accountId): ?string => $accountId?->id(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['accounts' => []]);
        $resolver->setAllowedTypes('accounts', 'array');
    }
}
