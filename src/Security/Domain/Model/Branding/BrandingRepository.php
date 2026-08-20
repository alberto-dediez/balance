<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Account\AccountId;

interface BrandingRepository
{
    public function add(Branding $branding): void;

    public function remove(Branding $branding): void;

    public function find(BrandingId $brandingId): ?Branding;

    public function findByAccountId(AccountId $accountId): ?Branding;

    /** @return list<Branding> */
    public function findByFilter(BrandingFilter $filter): array;

    public function totalResultsByFilter(BrandingFilter $filter): int;
}
