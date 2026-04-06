<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;

class ServiceCategoryMother
{
    public static function registeredServiceCategory(): ServiceCategory
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): ServiceCategory
    {
        return self::from(
            name: $name,
            id: (string) ServiceCategoryId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): ServiceCategory
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): ServiceCategory {
        $serviceCategory = new ServiceCategory(
            id: new ServiceCategoryId($id),
        );
        $serviceCategory->register($name);

        return $serviceCategory;
    }

    public static function random(): ServiceCategory
    {
        return self::from(
            name: 'serviceCategory-' . random_int(100, 999),
            id: (string) ServiceCategoryId::generate(),
        );
    }
}
