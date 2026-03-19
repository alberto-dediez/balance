<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Category;

use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;

class CategoryMother
{
    public static function fromName(string $name)
    {
        return self::from(
            categoryId: CategoryId::generate(),
            name: $name,
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            categoryId: new CategoryId($id),
            name: $name,
        );
    }

    public static function from(CategoryId $categoryId, string $name = 'name'): Category
    {
        $category = new Category(
            id: $categoryId,
        );

        $category->register($name);

        return $category;
    }

    public static function random()
    {
        return self::from(
            categoryId: CategoryId::generate(),
            name: 'category-' . random_int(100, 999),
        );
    }
}
