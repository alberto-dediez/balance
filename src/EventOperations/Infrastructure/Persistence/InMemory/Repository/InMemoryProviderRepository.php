<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Persistence\InMemory\Repository;

use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderFilter;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProviderRepository implements ProviderRepository
{
    /** @param array<string, Provider> $providers */
    public function __construct(
        private array $providers = [],
    ) {}

    public function add(Provider $provider): void
    {
        if (isset($this->providers[(string) $provider->id()])) {
            return;
        }

        $this->providers[(string) $provider->id()] = $provider;
    }

    public function remove(Provider $provider): void
    {
        if (!isset($this->providers[(string) $provider->id()])) {
            return;
        }

        unset($this->providers[(string) $provider->id()]);
    }

    public function find(ProviderId $providerId): ?Provider
    {
        return (isset($this->providers[(string) $providerId]) && false == $this->providers[(string) $providerId]->isRemoved())
            ? $this->providers[(string) $providerId]
            : null
        ;
    }

    /** @return array<int, Provider> */
    public function findByFilter(ProviderFilter $filter): array
    {
        $results = [];

        if (0 === count($this->providers)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->providers as $provider) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $provider->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && (null === $provider->accountId() || !$filter->accountId->equals($provider->accountId()))) {
                continue;
            }

            if (true == $provider->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $provider;
        }

        return $results;
    }

    public function totalResultsByFilter(ProviderFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
