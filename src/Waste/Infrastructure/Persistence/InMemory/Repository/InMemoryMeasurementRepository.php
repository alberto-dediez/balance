<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
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
    /** @param array<string, Measurement> $measurements */
    public function __construct(
        private array $measurements = [],
    ) {}

    public function add(Measurement $measurement): void
    {
        if (isset($this->measurements[(string) $measurement->id()])) {
            return;
        }

        $this->measurements[(string) $measurement->id()] = $measurement;
    }

    public function remove(Measurement $measurement): void
    {
        if (!isset($this->measurements[(string) $measurement->id()])) {
            return;
        }

        unset($this->measurements[(string) $measurement->id()]);
    }

    public function find(MeasurementId $measurementId): ?Measurement
    {
        return $this->measurements[(string) $measurementId] ?? null
        ;
    }

    /** @return array<int, Measurement> */
    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): array
    {
        $filter = new MeasurementFilter(
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            registeredAt: $day,
            limit: 0,
        );

        return $this->findByFilter($filter);
    }

    /** @return array<int, Measurement> */
    public function findByFilter(MeasurementFilter $filter): array
    {
        $results = [];

        if (0 === count($this->measurements)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->measurements as $measurement) {
            if (
                $filter->containerId instanceof ContainerId
                && (
                    null == $measurement->containerId()
                    || false === $filter->containerId->equals($measurement->containerId())
                )
            ) {
                continue;
            }

            if (
                $filter->wasteId instanceof WasteId
                && (
                    null == $measurement->wasteId()
                    || false === $filter->wasteId->equals($measurement->wasteId())
                )
            ) {
                continue;
            }

            if ($filter->purpose instanceof Purpose && $filter->purpose !== $measurement->purpose()) {
                continue;
            }

            if ($filter->measurementMethod instanceof MeasurementMethod && $filter->measurementMethod !== $measurement->measurementMethod()) {
                continue;
            }

            if (
                $filter->wasteManagerId instanceof WasteManagerId
                && (
                    null == $measurement->wasteManagerId()
                    || false === $filter->wasteManagerId->equals($measurement->wasteManagerId())
                )
            ) {
                continue;
            }

            if (
                $filter->propertyId instanceof PropertyId
                && (
                    null == $measurement->propertyId()
                    || false === $filter->propertyId->equals($measurement->propertyId())
                )
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (
                    null == $measurement->accountId()
                    || false === $filter->accountId->equals($measurement->accountId())
                )
            ) {
                continue;
            }

            if (
                $filter->userId instanceof UserId
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
                $filter->registeredAt instanceof \DateTimeImmutable
                && (
                    null == $measurement->registeredAt()
                    || $measurement->registeredAt()->format('Y-m-d') !== $filter->registeredAt->format('Y-m-d')
                )
            ) {
                continue;
            }

            if (
                $filter->registeredAtFrom instanceof \DateTimeImmutable
                && (
                    null == $measurement->registeredAt()
                    || $measurement->registeredAt() < $filter->registeredAtFrom->setTime(0, 0, 0)
                )
            ) {
                continue;
            }

            if (
                $filter->registeredAtTo instanceof \DateTimeImmutable
                && (
                    null == $measurement->registeredAt()
                    || $measurement->registeredAt() > $filter->registeredAtTo->setTime(23, 59, 59)
                )
            ) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

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
