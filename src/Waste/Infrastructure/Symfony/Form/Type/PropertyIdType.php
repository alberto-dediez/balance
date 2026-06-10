<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
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
            'class' => PropertyId::class,
            'account_id' => null,
        ]);

        $resolver->setAllowedTypes('account_id', ['null', \App\Security\Domain\Model\Account\AccountId::class]);

        $resolver->setNormalizer('choices', function (OptionsResolver $options, mixed $value): array {
            $accountId = $options['account_id'] ?? null;

            if (null === $accountId) {
                $account = ($this->loggedAccount)();
                $accountId = $account?->id();
            }

            $properties = $this->propertyRepository->findByFilter(
                new PropertyFilter(accountId: $accountId, limit: 0),
            );

            return $this->getChoices($properties);
        });
    }
}
