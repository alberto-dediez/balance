<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriticalPointIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly CriticalPointRepository $repository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $resolver->setDefaults([
            'class' => CriticalPointId::class,
            'property_id' => null,
        ]);

        $resolver->setAllowedTypes('property_id', ['null', PropertyId::class]);

        $resolver->setNormalizer('choices', function (Options $options) use ($account): array {
            $criticalPoints = $this->repository->findByFilter(
                new CriticalPointFilter(
                    accountId: $account instanceof Account ? $account->id() : null,
                    propertyId: $options['property_id'],
                    limit: 0,
                ),
            );

            return $this->getChoices($criticalPoints);
        });
    }
}
