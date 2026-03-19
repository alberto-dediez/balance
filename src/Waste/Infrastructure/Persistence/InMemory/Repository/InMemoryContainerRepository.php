<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContainerRepository implements ContainerRepository
{
    public function __construct(private array $containers = []) {}

    public function add(Container $container): void
    {
        if (isset($this->containers[(string) $container->id()])) {
            return;
        }

        $this->containers[(string) $container->id()] = $container;
    }

    public function remove(Container $container): void
    {
        if (!isset($this->containers[(string) $container->id()])) {
            return;
        }

        unset($this->containers[(string) $container->id()]);
    }

    public function find(ContainerId $containerId): ?Container
    {
        return (isset($this->containers[(string) $containerId]) && false == $this->containers[(string) $containerId]->isRemoved())
            ? $this->containers[(string) $containerId]
            : null
        ;
    }

    public function findByFilter(ContainerFilter $filter): array
    {
        $results = [];

        if (0 === count($this->containers)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->containers as $container) {
            if (
                $filter->propertyId instanceof PropertyId
                && (
                    null == $container->propertyId()
                    || false === $filter->propertyId->equals($container->propertyId())
                )
            ) {
                continue;
            }

            if (
                $filter->wasteId instanceof WasteId
                && (
                    null == $container->wasteId()
                    || false === $filter->wasteId->equals($container->wasteId())
                )
            ) {
                continue;
            }

            if ($filter->purpose instanceof Purpose && $filter->purpose !== $container->purpose()) {
                continue;
            }

            if ($filter->measurementMethod instanceof MeasurementMethod && $filter->measurementMethod !== $container->measurementMethod()) {
                continue;
            }

            if ($filter->wasteManagerId instanceof WasteManagerId && !$filter->wasteManagerId->equals($container->wasteManagerId())) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (
                    null == $container->accountId()
                    || false === $filter->accountId->equals($container->accountId())
                )
            ) {
                continue;
            }

            if (true == $container->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $container;
        }

        return $results;
    }

    public function totalResultsByFilter(ContainerFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
