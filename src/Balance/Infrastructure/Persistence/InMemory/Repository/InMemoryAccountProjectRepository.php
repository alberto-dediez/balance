<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAccountProjectRepository implements AccountProjectRepository
{
    /** @param array<string, AccountProject> $accountProjects */
    public function __construct(
        private array $accountProjects = [],
    ) {}

    public function add(AccountProject $accountProject): void
    {
        if (isset($this->accountProjects[(string) $accountProject->id()])) {
            return;
        }

        $this->accountProjects[(string) $accountProject->id()] = $accountProject;
    }

    public function remove(AccountProject $accountProject): void
    {
        if (!isset($this->accountProjects[(string) $accountProject->id()])) {
            return;
        }

        unset($this->accountProjects[(string) $accountProject->id()]);
    }

    public function find(AccountProjectId $accountProjectId): ?AccountProject
    {
        return $this->accountProjects[(string) $accountProjectId] ?? null;
    }

    private function matches(AccountProject $accountProject, AccountProjectFilter $filter): bool
    {
        if (
            null !== $filter->accountId
            && (null === $accountProject->accountId() || !$filter->accountId->equals($accountProject->accountId()))
        ) {
            return false;
        }

        if (
            null !== $filter->projectId
            && (null === $accountProject->projectId() || !$filter->projectId->equals($accountProject->projectId()))
        ) {
            return false;
        }

        return true;
    }

    /** @return array<int, AccountProject> */
    public function findByFilter(AccountProjectFilter $filter): array
    {
        $results = [];

        if (0 === count($this->accountProjects)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->accountProjects as $accountProject) {
            if (!$this->matches($accountProject, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $accountProject;
        }

        return $results;
    }

    public function totalResultsByFilter(AccountProjectFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
