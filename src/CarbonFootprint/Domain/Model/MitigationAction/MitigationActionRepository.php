<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

interface MitigationActionRepository
{
    public function add(MitigationAction $mitigationAction): void;

    public function remove(MitigationAction $mitigationAction): void;

    public function find(MitigationActionId $mitigationActionId): ?MitigationAction;

    /** @return array<MitigationAction> */
    public function findByFilter(MitigationActionFilter $filter): array;

    public function totalResultsByFilter(MitigationActionFilter $filter): int;
}
