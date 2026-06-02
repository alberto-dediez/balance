<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationFilter;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: AlertConfigurationRepository::class, public: true)]
#[When(env: 'test')]
class InMemoryAlertConfigurationRepository implements AlertConfigurationRepository
{
    /** @param array<string, AlertConfiguration> $configs */
    public function __construct(
        private array $configs = [],
    ) {}

    public function add(AlertConfiguration $config): void
    {
        $this->configs[(string) $config->id()] = $config;
    }

    public function save(AlertConfiguration $config): void
    {
        $this->configs[(string) $config->id()] = $config;
    }

    public function remove(AlertConfiguration $config): void
    {
        unset($this->configs[(string) $config->id()]);
    }

    public function find(AlertConfigurationId $id): ?AlertConfiguration
    {
        return $this->configs[(string) $id] ?? null;
    }

    public function findByAccount(AccountId $accountId): ?AlertConfiguration
    {
        foreach ($this->configs as $config) {
            if (null !== $config->accountId() && $config->accountId()->equals($accountId)) {
                return $config;
            }
        }

        return null;
    }

    /** @return array<int, AlertConfiguration> */
    public function findByFilter(AlertConfigurationFilter $filter): array
    {
        $results = [];

        if (0 === count($this->configs)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->configs as $config) {
            if (
                $filter->accountId instanceof AccountId
                && (null == $config->accountId() || false === $filter->accountId->equals($config->accountId()))
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

            $results[] = $config;
        }

        return $results;
    }

    public function totalResultsByFilter(AlertConfigurationFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
