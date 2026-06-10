<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Security\Domain\Model\Account\AccountId;

interface AlertConfigurationRepository
{
    public function add(AlertConfiguration $config): void;

    public function save(AlertConfiguration $config): void;

    public function remove(AlertConfiguration $config): void;

    public function find(AlertConfigurationId $id): ?AlertConfiguration;

    /** @return array<AlertConfiguration> */
    public function findByFilter(AlertConfigurationFilter $filter): array;

    public function totalResultsByFilter(AlertConfigurationFilter $filter): int;

    public function findByAccount(AccountId $accountId): ?AlertConfiguration;
}
