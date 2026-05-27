<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerFilter;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WasteManagerIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly WasteManagerRepository $repository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $resolver->setDefaults([
            'class' => WasteManagerId::class,
            'animal_feed_authorized' => null,
            'compost_authorized' => null,
        ]);

        $resolver->setAllowedTypes('animal_feed_authorized', ['null', 'bool']);
        $resolver->setAllowedTypes('compost_authorized', ['null', 'bool']);

        $resolver->setNormalizer('choices', function ($options) use ($account): array {
            $wasteManagers = $this->repository->findByFilter(
                new WasteManagerFilter(
                    accountId: $account instanceof Account ? $account->id() : null,
                    animalFeedAuthorized: $options['animal_feed_authorized'],
                    compostAuthorized: $options['compost_authorized'],
                    limit: 0,
                ),
            );

            return $this->getChoices($wasteManagers);
        });
    }
}
