<?php

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMeasurementRepository implements MeasurementRepository
{
    public function __construct(
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

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): array
    {
        $filter = new MeasurementFilter(
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            registeredAt: $day,
            limit: 0
        );

        return $this->findByFilter($filter);
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

            if (
                null !== $filter->wasteManagerId
                && (
                    null == $measurement->wasteManagerId()
                    || false == $filter->wasteManagerId->equals($measurement->wasteManagerId())
                )
            ) {
                continue;
            }

            if (
                null !== $filter->propertyId
                && (
                    null == $measurement->propertyId()
                    || false == $filter->propertyId->equals($measurement->propertyId())
                )
            ) {
                continue;
            }

            if (
                null !== $filter->accountId
                && (
                    null == $measurement->accountId()
                    || false == $filter->accountId->equals($measurement->accountId())
                )
            ) {
                continue;
            }

            if (
                null !== $filter->userId
                && (
                    null == $measurement->registeredBy()
                    || false == $measurement->registeredBy()->equals($filter->userId)
                )
            ) {
                continue;
            }

            if (
                !empty($filter->reference)
                && $filter->reference !== $measurement->reference()
            ) {
                continue;
            }

            if (
                null !== $filter->registeredAt
                && (
                    null == $measurement->registeredAt()
                    || $measurement->registeredAt()->format('Y-m-d') !== $filter->registeredAt->format('Y-m-d')
                )
            ) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
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
