<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Waste\WasteId;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: AlertRepository::class, public: true)]
#[When(env: 'test')]
class InMemoryAlertRepository implements AlertRepository
{
    /** @param array<string, Alert> $alerts */
    public function __construct(
        private array $alerts = [],
    ) {}

    public function add(Alert $alert): void
    {
        $this->alerts[(string) $alert->id()] = $alert;
    }

    /**
     * @param array<Alert> $alerts
     */
    public function addMultiple(array $alerts): void
    {
        if (0 === count($alerts)) {
            return;
        }

        foreach ($alerts as $alert) {
            $this->add($alert);
        }
    }

    public function find(AlertId $alertId): ?Alert
    {
        return $this->alerts[(string) $alertId] ?? null;
    }

    /** @return array<int, Alert> */
    public function findByFilter(AlertFilter $filter): array
    {
        $results = [];

        if (0 === count($this->alerts)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->alerts as $alert) {
            if (
                $filter->accountId instanceof AccountId
                && (
                    null == $alert->accountId()
                    || false === $filter->accountId->equals($alert->accountId())
                )
            ) {
                continue;
            }

            if (
                $filter->propertyId instanceof PropertyId
                && (
                    null == $alert->propertyId()
                    || false === $filter->propertyId->equals($alert->propertyId())
                )
            ) {
                continue;
            }

            if (
                $filter->wasteId instanceof WasteId
                && (
                    null == $alert->wasteId()
                    || false === $filter->wasteId->equals($alert->wasteId())
                )
            ) {
                continue;
            }

            if ($filter->type instanceof AlertType && $filter->type !== $alert->type()) {
                continue;
            }

            if ($filter->status instanceof AlertStatus && $filter->status !== $alert->status()) {
                continue;
            }

            if (
                $filter->from instanceof \DateTimeImmutable
                && $alert->detectedAt() < $filter->from
            ) {
                continue;
            }

            if (
                $filter->to instanceof \DateTimeImmutable
                && $alert->detectedAt() > $filter->to
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

            $results[] = $alert;
        }

        return $results;
    }

    public function totalResultsByFilter(AlertFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
