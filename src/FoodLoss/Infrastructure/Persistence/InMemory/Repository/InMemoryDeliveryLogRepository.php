<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryDeliveryLogRepository implements DeliveryLogRepository
{
    /** @param array<string, DeliveryLog> $deliveryLogs */
    public function __construct(
        private array $deliveryLogs = [],
    ) {}

    public function add(DeliveryLog $deliveryLog): void
    {
        if (isset($this->deliveryLogs[(string) $deliveryLog->id()])) {
            return;
        }

        $this->deliveryLogs[(string) $deliveryLog->id()] = $deliveryLog;
    }

    public function remove(DeliveryLog $deliveryLog): void
    {
        if (!isset($this->deliveryLogs[(string) $deliveryLog->id()])) {
            return;
        }

        unset($this->deliveryLogs[(string) $deliveryLog->id()]);
    }

    public function find(DeliveryLogId $deliveryLogId): ?DeliveryLog
    {
        return (isset($this->deliveryLogs[(string) $deliveryLogId]) && false == $this->deliveryLogs[(string) $deliveryLogId]->isRemoved())
            ? $this->deliveryLogs[(string) $deliveryLogId]
            : null
        ;
    }

    public function findBySurplusId(SurplusId $surplusId): ?DeliveryLog
    {
        foreach ($this->deliveryLogs as $deliveryLog) {
            if ($deliveryLog->isRemoved()) {
                continue;
            }

            if (null !== $deliveryLog->surplusId() && $deliveryLog->surplusId()->equals($surplusId)) {
                return $deliveryLog;
            }
        }

        return null;
    }

    /** @return array<int, DeliveryLog> */
    public function findByFilter(DeliveryLogFilter $filter): array
    {
        $results = [];

        if (0 === count($this->deliveryLogs)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->deliveryLogs as $deliveryLog) {
            if (true == $deliveryLog->isRemoved()) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $deliveryLog->accountId() || !$filter->accountId->equals($deliveryLog->accountId()))) {
                continue;
            }

            if ($filter->recipientOrganizationId instanceof RecipientOrganizationId && (null === $deliveryLog->recipientOrganizationId() || !$filter->recipientOrganizationId->equals($deliveryLog->recipientOrganizationId()))) {
                continue;
            }

            if ($filter->propertyId instanceof PropertyId && (null === $deliveryLog->propertyId() || !$filter->propertyId->equals($deliveryLog->propertyId()))) {
                continue;
            }

            if (!empty($filter->productType) && !str_contains(strtolower($deliveryLog->productType()), strtolower($filter->productType))) {
                continue;
            }

            if ($filter->dateFrom instanceof \DateTimeImmutable && $deliveryLog->date() < $filter->dateFrom) {
                continue;
            }

            if ($filter->dateTo instanceof \DateTimeImmutable && $deliveryLog->date() > $filter->dateTo) {
                continue;
            }

            if ($offset > 0) {
                --$offset;
                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $deliveryLog;
        }

        return $results;
    }

    public function totalResultsByFilter(DeliveryLogFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
