<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Company;

interface CompanyRepository
{
    public function add(Company $company): void;

    public function remove(Company $company): void;

    public function find(CompanyId $companyId): ?Company;

    /** @return array<Company> */
    public function findByFilter(CompanyFilter $filter): array;

    public function totalResultsByFilter(CompanyFilter $filter): int;
}
