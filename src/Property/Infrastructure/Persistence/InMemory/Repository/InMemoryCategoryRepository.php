<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryFilter;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCategoryRepository implements CategoryRepository
{
    /** @param Category[] $categories */
    public function __construct(private array $categories = []) {}

    public function add(Category $category): void
    {
        if (isset($this->categories[(string) $category->id()])) {
            return;
        }

        $this->categories[(string) $category->id()] = $category;
    }

    public function remove(Category $category): void
    {
        if (!isset($this->categories[(string) $category->id()])) {
            return;
        }

        unset($this->categories[(string) $category->id()]);
    }

    public function find(CategoryId $categoryId): ?Category
    {
        return (isset($this->categories[(string) $categoryId]) && false == $this->categories[(string) $categoryId]->isRemoved())
            ? $this->categories[(string) $categoryId]
            : null
        ;
    }

    public function findByName(string $name): ?Category
    {
        if (0 === count($this->categories)) {
            return null;
        }

        foreach ($this->categories as $category) {
            if (strtolower($category->name()) === strtolower($name)) {
                return $this->find($category->id());
            }
        }

        return null;
    }

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Category
    {
        if (0 === count($this->categories)) {
            return null;
        }

        $sourceId = $source->id($externalId);
        $attribute = $source->attribute();

        foreach ($this->categories as $category) {
            if ($category->externalId()->isEmpty()) {
                continue;
            }

            if ($category->externalId()->$attribute() === $sourceId) {
                return $this->find($category->id());
            }
        }

        return null;
    }

    /** @return Category[] */
    public function findByFilter(CategoryFilter $filter): array
    {
        $results = [];

        if (0 === count($this->categories)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->categories as $category) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $category->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $category->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $category;
        }

        return $results;
    }

    public function totalResultsByFilter(CategoryFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
