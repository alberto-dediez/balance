<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCompanyRepository implements CompanyRepository
{
    /** @param array<string, Company> $companies */
    public function __construct(
        private array $companies = [],
    ) {}

    public function add(Company $company): void
    {
        if (isset($this->companies[(string) $company->id()])) {
            return;
        }

        $this->companies[(string) $company->id()] = $company;
    }

    public function remove(Company $company): void
    {
        if (!isset($this->companies[(string) $company->id()])) {
            return;
        }

        unset($this->companies[(string) $company->id()]);
    }

    public function find(CompanyId $companyId): ?Company
    {
        return (isset($this->companies[(string) $companyId]) && false == $this->companies[(string) $companyId]->isRemoved())
            ? $this->companies[(string) $companyId]
            : null
        ;
    }

    /** @return array<int, Company> */
    public function findByFilter(CompanyFilter $filter): array
    {
        $results = [];

        if (0 === count($this->companies)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->companies as $company) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $company->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $company->accountId() || !$filter->accountId->equals($company->accountId()))) {
                continue;
            }

            if (true == $company->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $company;
        }

        return $results;
    }

    public function totalResultsByFilter(CompanyFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
