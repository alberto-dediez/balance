<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\DataMapper;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GroupedEmissionFactorsType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class GroupedEmissionFactorsDataMapper implements DataMapperInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    /** @param array<EmissionFactorGroup> $groups */
    public function __construct(
        private readonly array $groups,
        private readonly string $groupIdProperty = 'emissionFactorGroupId',
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /** @param \Traversable<array-key, FormInterface<mixed>> $forms */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (null === $viewData) {
            $viewData = [];
        }

        if (!is_iterable($viewData)) {
            throw new UnexpectedTypeException($viewData, 'iterable');
        }

        $buckets = [];
        foreach ($this->groups as $group) {
            $buckets[GroupedEmissionFactorsType::groupKey($group)] = [];
        }

        foreach ($viewData as $row) {
            $groupId = (string) $this->propertyAccessor->getValue($row, $this->groupIdProperty);

            foreach ($this->groups as $group) {
                if ((string) $group->id() === $groupId) {
                    $buckets[GroupedEmissionFactorsType::groupKey($group)][] = $row;

                    break;
                }
            }
        }

        foreach ($forms as $form) {
            $form->setData($buckets[$form->getName()] ?? []);
        }
    }

    /** @param \Traversable<array-key, FormInterface<mixed>> $forms */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $rows = [];

        foreach ($forms as $form) {
            $groupId = $this->groupIdForKey($form->getName());

            /** @var array<mixed> $groupRows */
            $groupRows = $form->getData();

            foreach ($groupRows as $row) {
                if (null !== $groupId) {
                    $this->propertyAccessor->setValue($row, $this->groupIdProperty, $groupId);
                }

                $rows[] = $row;
            }
        }

        $viewData = $rows;
    }

    private function groupIdForKey(string $key): ?string
    {
        foreach ($this->groups as $group) {
            if (GroupedEmissionFactorsType::groupKey($group) === $key) {
                return (string) $group->id();
            }
        }

        return null;
    }
}
