<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;

class MitigationMeasureCategoryMother
{
    public static function registeredMitigationMeasureCategory(): MitigationMeasureCategory
    {
        return self::fromName('category-name');
    }

    public static function fromName(string $name): MitigationMeasureCategory
    {
        return self::from(
            name: $name,
            id: (string) MitigationMeasureCategoryId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): MitigationMeasureCategory
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(string $name, string $id): MitigationMeasureCategory
    {
        $category = new MitigationMeasureCategory(
            id: new MitigationMeasureCategoryId($id),
        );
        $category->register($name);

        return $category;
    }

    public static function random(): MitigationMeasureCategory
    {
        return self::fromName('category-' . rand(100, 999));
    }
}
