<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionFilter;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryOrganizationalProviderContributionRepository implements OrganizationalProviderContributionRepository
{
    /** @param array<string, OrganizationalProviderContribution> $contributions */
    public function __construct(
        private array $contributions = [],
    ) {}

    public function add(OrganizationalProviderContribution $contribution): void
    {
        $this->contributions[(string) $contribution->id()] = $contribution;
    }

    public function find(OrganizationalProviderContributionId $contributionId): ?OrganizationalProviderContribution
    {
        $contribution = $this->contributions[(string) $contributionId] ?? null;

        return null !== $contribution && !$contribution->isRemoved() ? $contribution : null;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?OrganizationalProviderContribution
    {
        if ('' === $token) {
            return null;
        }

        foreach ($this->contributions as $contribution) {
            if (!$contribution->isRemoved() && hash_equals($contribution->token(), $token)) {
                return $contribution;
            }
        }

        return null;
    }

    /** @return list<OrganizationalProviderContribution> */
    public function findActiveByFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        return array_values($this->findByFilter(new OrganizationalProviderContributionFilter(
            subjectType: $subjectType,
            carbonFootprintId: $carbonFootprintId,
            limit: 0,
        )));
    }

    public function findActiveByFootprintAndProvider(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        ProviderId $providerId,
    ): ?OrganizationalProviderContribution {
        foreach ($this->findActiveByFootprint($subjectType, $carbonFootprintId) as $contribution) {
            if (null !== $contribution->providerId() && $providerId->equals($contribution->providerId())) {
                return $contribution;
            }
        }

        return null;
    }

    /** @return array<int, OrganizationalProviderContribution> */
    public function findByFilter(OrganizationalProviderContributionFilter $filter): array
    {
        $contributions = array_values($this->contributions);
        usort(
            $contributions,
            static fn(OrganizationalProviderContribution $a, OrganizationalProviderContribution $b): int => $a->registeredAt() <=> $b->registeredAt(),
        );

        $results = [];
        $offset = $filter->page * $filter->limit;

        foreach ($contributions as $contribution) {
            if ($contribution->isRemoved()) {
                continue;
            }

            if (null !== $filter->subjectType && $contribution->subjectType() !== $filter->subjectType) {
                continue;
            }

            if (null !== $filter->carbonFootprintId && $contribution->carbonFootprintId() !== $filter->carbonFootprintId) {
                continue;
            }

            if (null !== $filter->accountId && (null === $contribution->accountId() || !$filter->accountId->equals($contribution->accountId()))) {
                continue;
            }

            if (null !== $filter->status && $contribution->status() !== $filter->status) {
                continue;
            }

            if (null !== $filter->mode && $contribution->mode() !== $filter->mode) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if ($filter->limit > 0 && count($results) >= $filter->limit) {
                continue;
            }

            $results[] = $contribution;
        }

        return $results;
    }

    public function totalResultsByFilter(OrganizationalProviderContributionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
