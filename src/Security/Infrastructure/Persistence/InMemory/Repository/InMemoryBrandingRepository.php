<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingFilter;
use App\Security\Domain\Model\Branding\BrandingId;
use App\Security\Domain\Model\Branding\BrandingRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryBrandingRepository implements BrandingRepository
{
    /**
     * @param Branding[] $brandings
     */
    public function __construct(private array $brandings = []) {}

    public function add(Branding $branding): void
    {
        $this->brandings[(string) $branding->id()] = $branding;
    }

    public function remove(Branding $branding): void
    {
        unset($this->brandings[(string) $branding->id()]);
    }

    public function find(BrandingId $brandingId): ?Branding
    {
        return (isset($this->brandings[(string) $brandingId]) && false == $this->brandings[(string) $brandingId]->isRemoved())
            ? $this->brandings[(string) $brandingId]
            : null
        ;
    }

    public function findByAccountId(AccountId $accountId): ?Branding
    {
        foreach ($this->brandings as $branding) {
            if ($branding->accountId()->equals($accountId)) {
                return $this->find($branding->id());
            }
        }

        return null;
    }

    /**
     * @return list<Branding>
     */
    public function findByFilter(BrandingFilter $filter): array
    {
        $results = [];

        $offset = $filter->page * $filter->limit;

        foreach ($this->brandings as $branding) {
            if ($filter->accountId instanceof AccountId && !$branding->accountId()->equals($filter->accountId)) {
                continue;
            }

            if (!in_array($filter->themeSlug, [null, '', '0'], true) && $filter->themeSlug !== $branding->themeSlug()) {
                continue;
            }

            if (true == $branding->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $branding;
        }

        return $results;
    }

    public function totalResultsByFilter(BrandingFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
