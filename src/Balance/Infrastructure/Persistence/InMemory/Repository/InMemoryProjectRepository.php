<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProjectRepository implements ProjectRepository
{
    /** @param array<string, Project> $projects */
    public function __construct(
        private array $projects = [],
        private ?AccountProjectRepository $accountProjectRepository = null,
    ) {}

    public function add(Project $project): void
    {
        if (isset($this->projects[(string) $project->id()])) {
            return;
        }

        $this->projects[(string) $project->id()] = $project;
    }

    public function remove(Project $project): void
    {
        if (!isset($this->projects[(string) $project->id()])) {
            return;
        }

        unset($this->projects[(string) $project->id()]);
    }

    public function find(ProjectId $projectId): ?Project
    {
        return (isset($this->projects[(string) $projectId]) && false == $this->projects[(string) $projectId]->isRemoved())
            ? $this->projects[(string) $projectId]
            : null
        ;
    }

    private function isSelected(Project $project, ProjectFilter $filter): bool
    {
        if (null === $this->accountProjectRepository || null === $filter->selectedByAccountId) {
            return false;
        }

        $accountProjects = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $filter->selectedByAccountId, projectId: $project->id(), limit: 1),
        );

        return count($accountProjects) > 0;
    }

    /** @return array<int, Project> */
    public function findByFilter(ProjectFilter $filter): array
    {
        $results = [];

        if (0 === count($this->projects)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->projects as $project) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower($project->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->location && (null === $project->location() || !$filter->location->equals($project->location()))) {
                continue;
            }

            if (null !== $filter->projectType && $filter->projectType !== $project->projectType()) {
                continue;
            }

            if (null !== $filter->projectStatus && $filter->projectStatus !== $project->projectStatus()) {
                continue;
            }

            if (
                !empty($filter->mitecoId)
                && !str_contains(strtolower((string) $project->mitecoId()), strtolower($filter->mitecoId))
            ) {
                continue;
            }

            if (true == $project->isRemoved()) {
                continue;
            }

            if (null !== $filter->selected && $filter->selected !== $this->isSelected($project, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $project;
        }

        return $results;
    }

    public function totalResultsByFilter(ProjectFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
