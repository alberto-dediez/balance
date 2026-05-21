<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
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
        private readonly PropertyExtension $propertyExtension,
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

            return !empty($options['property_id']) ? $this->getChoices($criticalPoints) : $this->getChoicesWithoutProperty($criticalPoints);
        });
    }

    /**
     * @param array<int, mixed> $aggregateRootElements
     *
     * @return array<string, mixed>
     */
    protected function getChoicesWithoutProperty(array $aggregateRootElements): array
    {
        if (0 === count($aggregateRootElements)) {
            return [];
        }

        $choices = [];

        /** @var CriticalPoint $element */
        foreach ($aggregateRootElements as $element) {
            $propertyName = null !== $element->propertyId() ? (string) $this->propertyExtension->propertyName($element->propertyId()) : '';

            $name = sprintf('%s - %s', $propertyName, $element->name());

            $i = 0;

            while (isset($choices[$name])) {
                $name = sprintf('%s - %s (%d)', $propertyName, $element->name(), ++$i);
            }

            $choices[$name] = (string) $element->id();
        }

        return $choices;
    }
}
