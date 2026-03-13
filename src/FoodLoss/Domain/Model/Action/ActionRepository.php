<?php

namespace App\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionFilter;

interface ActionRepository
{
    public function add(Action $action): void;

    public function remove(Action $action): void;

    public function find(ActionId $actionId): ?Action;

    public function findByFilter(ActionFilter $filter): array;

    public function totalResultsByFilter(ActionFilter $filter): int;
}