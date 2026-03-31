<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

interface ActionRepository
{
    public function add(Action $action): void;

    public function remove(Action $action): void;

    public function find(ActionId $actionId): ?Action;

    /** @return array<Action> */
    public function findByFilter(ActionFilter $filter): array;

    public function totalResultsByFilter(ActionFilter $filter): int;
}
