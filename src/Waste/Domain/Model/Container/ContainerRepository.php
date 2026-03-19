<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

interface ContainerRepository
{
    public function add(Container $container): void;

    public function remove(Container $container): void;

    public function find(ContainerId $containerId): ?Container;

    public function findByFilter(ContainerFilter $filter): array;

    public function totalResultsByFilter(ContainerFilter $filter): int;
}
