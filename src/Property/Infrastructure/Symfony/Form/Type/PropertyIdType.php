<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'all_accounts' => false,
            'choices' => fn(Options $options): array => $this->getChoices(
                $this->propertyRepository->findByFilter(
                    new PropertyFilter(
                        accountId: $options['all_accounts'] ? null : ($this->loggedAccount)()?->id(),
                        limit: 0,
                    ),
                ),
            ),
            'class' => PropertyId::class,
        ]);

        $resolver->setAllowedTypes('all_accounts', 'bool');
    }
}
