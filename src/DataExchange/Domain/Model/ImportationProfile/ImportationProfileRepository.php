<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\ImportationProfile;

use App\Security\Domain\Model\Account\AccountId;

interface ImportationProfileRepository
{
    public function add(ImportationProfile $importationProfile): void;

    public function find(ImportationProfileId $importationProfileId): ?ImportationProfile;

    public function findByTypeAndAccount(string $type, ?AccountId $accountId): ?ImportationProfile;

    public function remove(ImportationProfile $importationProfile): void;
}
