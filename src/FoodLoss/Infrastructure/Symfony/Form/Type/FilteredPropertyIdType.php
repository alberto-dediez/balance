<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilteredPropertyIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $account = ($this->loggedAccount)();

        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(
                accountId: $account instanceof Account ? $account->id() : null,
                limit: 0,
            ),
        );

        $resolver->setDefaults([
            'class' => PropertyId::class,
            'multiple' => false,
            'required' => false,
            'allowed_property_ids' => [],
        ]);

        $resolver->setAllowedTypes('allowed_property_ids', 'array');

        $resolver->setNormalizer('choices', function (OptionsResolver $options) use ($properties): array {
            /** @var PropertyId[] $allowedIds */
            $allowedIds = $options['allowed_property_ids'];

            if ([] === $allowedIds) {
                return $this->getChoices($properties);
            }

            $filtered = array_filter(
                $properties,
                static function (mixed $property) use ($allowedIds): bool {
                    foreach ($allowedIds as $allowedId) {
                        if ($allowedId->equals($property->id())) {
                            return true;
                        }
                    }

                    return false;
                },
            );

            return $this->getChoices(array_values($filtered));
        });
    }
}
