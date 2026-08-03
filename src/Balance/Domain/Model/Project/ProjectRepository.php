<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

interface ProjectRepository
{
    public function add(Project $project): void;

    public function remove(Project $project): void;

    public function find(ProjectId $projectId): ?Project;

    /** @return array<Project> */
    public function findByFilter(ProjectFilter $filter): array;

    public function totalResultsByFilter(ProjectFilter $filter): int;
}
