<?php

namespace App\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerFilter;

interface ContainerRepository
{
    public function add(Container $container): void;

    public function remove(Container $container): void;

    public function find(ContainerId $containerId): ?Container;

    public function findByFilter(ContainerFilter $filter): array;

    public function totalResultsByFilter(ContainerFilter $filter): int;
}