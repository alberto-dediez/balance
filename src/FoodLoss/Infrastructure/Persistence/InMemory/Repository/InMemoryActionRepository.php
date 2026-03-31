<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryActionRepository implements ActionRepository
{
    /** @param array<string, Action> $actions */
    public function __construct(
        private array $actions = [],
    ) {}

    public function add(Action $action): void
    {
        if (isset($this->actions[(string) $action->id()])) {
            return;
        }

        $this->actions[(string) $action->id()] = $action;
    }

    public function remove(Action $action): void
    {
        if (!isset($this->actions[(string) $action->id()])) {
            return;
        }

        unset($this->actions[(string) $action->id()]);
    }

    public function find(ActionId $actionId): ?Action
    {
        return (isset($this->actions[(string) $actionId]) && false == $this->actions[(string) $actionId]->isRemoved())
            ? $this->actions[(string) $actionId]
            : null
        ;
    }

    /** @return array<int, Action> */
    public function findByFilter(ActionFilter $filter): array
    {
        $results = [];

        if (0 === count($this->actions)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->actions as $action) {
            if (
                !empty($filter->description)
                && !str_contains(strtolower((string) $action->description()), strtolower($filter->description))
            ) {
                continue;
            }

            if (
                !empty($filter->priority)
                && !str_contains(strtolower($action->priority()->value), strtolower($filter->priority->value))
            ) {
                continue;
            }

            if (true == $action->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $action;
        }

        return $results;
    }

    public function totalResultsByFilter(ActionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
