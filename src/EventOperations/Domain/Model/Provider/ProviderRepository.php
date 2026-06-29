<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

interface ProviderRepository
{
    public function add(Provider $provider): void;

    public function remove(Provider $provider): void;

    public function find(ProviderId $providerId): ?Provider;

    /** @return array<Provider> */
    public function findByFilter(ProviderFilter $filter): array;

    public function totalResultsByFilter(ProviderFilter $filter): int;
}
