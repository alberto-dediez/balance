<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementFilter;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryDonationAgreementRepository implements DonationAgreementRepository
{
    /** @param array<string, DonationAgreement> $donationAgreements */
    public function __construct(
        private array $donationAgreements = [],
    ) {}

    public function add(DonationAgreement $donationAgreement): void
    {
        $this->donationAgreements[(string) $donationAgreement->id()] = $donationAgreement;
    }

    public function remove(DonationAgreement $donationAgreement): void
    {
        unset($this->donationAgreements[(string) $donationAgreement->id()]);
    }

    public function find(DonationAgreementId $donationAgreementId): ?DonationAgreement
    {
        $donationAgreement = $this->donationAgreements[(string) $donationAgreementId] ?? null;

        return ($donationAgreement && false == $donationAgreement->isRemoved())
            ? $donationAgreement
            : null
        ;
    }

    /** @return array<int, DonationAgreement> */
    public function findByFilter(DonationAgreementFilter $filter): array
    {
        $results = [];

        foreach ($this->donationAgreements as $donationAgreement) {
            if (true == $donationAgreement->isRemoved()) {
                continue;
            }

            if (
                $filter->recipientOrganizationId instanceof RecipientOrganizationId
                && (string) $donationAgreement->recipientOrganizationId() !== (string) $filter->recipientOrganizationId
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (string) $donationAgreement->accountId() !== (string) $filter->accountId
            ) {
                continue;
            }

            $results[] = $donationAgreement;
        }

        // Sorting (default by registeredAt DESC)
        usort($results, function (DonationAgreement $a, DonationAgreement $b) use ($filter): int {
            $way = 'ASC' === $filter->orderWay ? 1 : -1;

            $valA = match ($filter->orderBy) {
                'startDate' => $a->startDate(),
                'expirationDate' => $a->expirationDate(),
                default => $a->registeredAt(),
            };

            $valB = match ($filter->orderBy) {
                'startDate' => $b->startDate(),
                'expirationDate' => $b->expirationDate(),
                default => $b->registeredAt(),
            };

            return ($valA <=> $valB) * $way;
        });

        if ($filter->limit > 0) {
            return array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(DonationAgreementFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
