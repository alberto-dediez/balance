<?php

namespace App\Waste\Infrastructure\Repository\Container;

use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerFilter;
use App\Waste\Domain\Model\Container\ContainerRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContainerRepository implements ContainerRepository
{
    public function __construct(private array $containers = [])
    {

    }

    public function add(Container $container): void
    {
        if (isset($this->containers[(string) $container->id()])) {
            return ;
        }

        $this->containers[(string) $container->id()] = $container;
    }

    public function remove(Container $container): void
    {
        if (!isset($this->containers[(string) $container->id()])) {
            return ;
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

        if (count($this->containers) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->containers as $container) {
            if (
                null !== $filter->propertyId
                && (
                    null == $container->propertyId() 
                    || false == $filter->propertyId->equals($container->propertyId())
                )
            ) {
                continue;
            }

            if (
                null !== $filter->wasteId 
                && (
                    null == $container->wasteId() 
                    || false == $filter->wasteId->equals($container->wasteId())
                )
            ) {
                continue;
            }

            if (null !== $filter->purpose && $filter->purpose !== $container->purpose()) {
                continue;
            }

            if (null !== $filter->measurementMethod && $filter->measurementMethod !== $container->measurementMethod()) {
                continue;
            }

            if (null !== $filter->wasteManagerId && !$filter->wasteManagerId->equals($container->wasteManagerId())) {
                continue;
            }

            if (
                null !== $filter->accountId
                && (
                    null == $container->accountId()
                    || false == $filter->accountId->equals($container->accountId())
                )
            ) {
                continue;
            }

            if (true == $container->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit) {
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