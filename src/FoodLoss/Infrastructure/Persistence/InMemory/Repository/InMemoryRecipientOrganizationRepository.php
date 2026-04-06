<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationFilter;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryRecipientOrganizationRepository implements RecipientOrganizationRepository
{
    /** @param array<string, RecipientOrganization> $recipientOrganizations */
    public function __construct(
        private array $recipientOrganizations = [],
    ) {}

    public function add(RecipientOrganization $recipientOrganization): void
    {
        if (isset($this->recipientOrganizations[(string) $recipientOrganization->id()])) {
            return;
        }

        $this->recipientOrganizations[(string) $recipientOrganization->id()] = $recipientOrganization;
    }

    public function remove(RecipientOrganization $recipientOrganization): void
    {
        if (!isset($this->recipientOrganizations[(string) $recipientOrganization->id()])) {
            return;
        }

        unset($this->recipientOrganizations[(string) $recipientOrganization->id()]);
    }

    public function find(RecipientOrganizationId $recipientOrganizationId): ?RecipientOrganization
    {
        return (isset($this->recipientOrganizations[(string) $recipientOrganizationId]) && false == $this->recipientOrganizations[(string) $recipientOrganizationId]->isRemoved())
            ? $this->recipientOrganizations[(string) $recipientOrganizationId]
            : null
        ;
    }

    /** @return array<int, RecipientOrganization> */
    public function findByFilter(RecipientOrganizationFilter $filter): array
    {
        $results = [];

        if (0 === count($this->recipientOrganizations)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->recipientOrganizations as $recipientOrganization) {
            if (true == $recipientOrganization->isRemoved()) {
                continue;
            }

            if (
                !empty($filter->name)
                && !str_contains(strtolower($recipientOrganization->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (
                !empty($filter->contact)
                && !str_contains(strtolower($recipientOrganization->contact()->name()), strtolower($filter->contact))
                && !str_contains(strtolower($recipientOrganization->contact()->email()), strtolower($filter->contact))
                && !str_contains(strtolower($recipientOrganization->contact()->phone()), strtolower($filter->contact))
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (null == $recipientOrganization->accountId()
                    || false === $filter->accountId->equals($recipientOrganization->accountId()))
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

            $results[] = $recipientOrganization;
        }

        return $results;
    }

    public function totalResultsByFilter(RecipientOrganizationFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
