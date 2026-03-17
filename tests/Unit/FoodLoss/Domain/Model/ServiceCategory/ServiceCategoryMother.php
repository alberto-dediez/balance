<?php

namespace App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;

class ServiceCategoryMother
{
    public static function registeredServiceCategory()
    {   
        return self::fromName('name');
    }

    public static function fromName($name)
    {
        return self::from(
            name: $name,
            id: (string) ServiceCategoryId::generate()
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            name: $name,
            id: $id
        );
    }

    public static function from(
        string $name,
        string $id
    ) {
        $serviceCategory = new ServiceCategory(
            id: new ServiceCategoryId($id)
        );
        $serviceCategory->register($name);

        return $serviceCategory;
    }

    public static function random()
    {
        return self::from(
            name: 'serviceCategory-' . rand(100, 999),
            id: (string) ServiceCategoryId::generate()
        );
    }
}