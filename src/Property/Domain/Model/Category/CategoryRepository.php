<?php

namespace App\Property\Domain\Model\Category;

use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Category\CategoryFilter;
use App\Property\Domain\Model\Property\ExternalSource;

interface CategoryRepository
{
    public function add(Category $category): void;

    public function remove(Category $category): void;

    public function find(CategoryId $categoryId): ?Category;

    public function findByName(string $name): ?Category;

    public function findBySourceAndExternalId(ExternalSource $source, ExternalId $externalId): ?Category;

    public function findByFilter(CategoryFilter $filter): array;

    public function totalResultsByFilter(CategoryFilter $filter): int;
}