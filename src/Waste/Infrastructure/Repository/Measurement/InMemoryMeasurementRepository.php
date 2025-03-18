<?php

namespace App\Waste\Infrastructure\Repository\Measurement;

use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use Symfony\Component\DependencyInjection\Attribute\When;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

#[When(env: 'test')]
class InMemoryMeasurementRepository implements MeasurementRepository
{
    public function __construct(
        private ContainerRepository $containerRepository, 
        private PropertyRepository $propertyRepository, 
        private array $measurements = []
    ) {

    }

    public function add(Measurement $measurement): void
    {
        if (isset($this->measurements[(string) $measurement->id()])) {
            return ;
        }

        $this->measurements[(string) $measurement->id()] = $measurement;
    }

    public function remove(Measurement $measurement): void
    {
        if (!isset($this->measurements[(string) $measurement->id()])) {
            return ;
        }

        unset($this->measurements[(string) $measurement->id()]);
    }

    public function find(MeasurementId $measurementId): ?Measurement
    {
        return (isset($this->measurements[(string) $measurementId])) 
            ? $this->measurements[(string) $measurementId]
            : null
        ;
    }

    public function findByFilter(MeasurementFilter $filter): array
    {
        $results = [];

        if (count($this->measurements) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->measurements as $measurement) {
            if (
                null !== $filter->containerId
                && (
                    null == $measurement->containerId() 
                    || false == $filter->containerId->equals($measurement->containerId())
                )
            ) {
                continue;
            }

            if (
                null !== $filter->wasteId 
                && (
                    null == $measurement->wasteId() 
                    || false == $filter->wasteId->equals($measurement->wasteId())
                )
            ) {
                continue;
            }

            if (null !== $filter->purpose && $filter->purpose !== $measurement->purpose()) {
                continue;
            }

            if (null !== $filter->measurementMethod && $filter->measurementMethod !== $measurement->measurementMethod()) {
                continue;
            }

            $container = $this->containerRepository->find($measurement->containerId());

            if (
                null !== $filter->propertyId
                && (
                    null == $container
                    || null == $container->propertyId() 
                    || false == $filter->propertyId->equals($container->propertyId())
                )
            ) {
                continue;
            }

            $property = null !== $container
                ? $this->propertyRepository->find($container->propertyId())
                : null
            ;

            if (
                null !== $filter->account 
                && (
                    null == $property 
                    || null == $property->accountId() 
                    || false == $filter->account->id()->equals($property->accountId())
                )
            ) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit) {
                continue;
            }

            $results[] = $measurement;
        }

        return $results;
    }

    public function totalResultsByFilter(MeasurementFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}