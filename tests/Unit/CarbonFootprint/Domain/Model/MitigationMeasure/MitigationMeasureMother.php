<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;

class MitigationMeasureMother
{
    public static function registeredMitigationMeasure(): MitigationMeasure
    {
        return self::fromName('measure-name');
    }

    public static function fromName(string $name): MitigationMeasure
    {
        return self::from(
            name: $name,
            id: (string) MitigationMeasureId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): MitigationMeasure
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function withEffect(
        Source $source,
        float $minimumReduction,
        float $maximumReduction,
        string $name = 'measure-name',
        ?MitigationMeasureCategoryId $categoryId = null,
    ): MitigationMeasure {
        $measure = self::from(
            name: $name,
            id: (string) MitigationMeasureId::generate(),
            categoryId: $categoryId,
        );
        $measure->setEffect($source, $minimumReduction, $maximumReduction);

        return $measure;
    }

    public static function from(
        string $name,
        string $id,
        string $description = 'measure-description',
        ?MitigationMeasureCategoryId $categoryId = null,
    ): MitigationMeasure {
        $measure = new MitigationMeasure(
            id: new MitigationMeasureId($id),
        );
        $measure->register($name, $description, $categoryId ?? MitigationMeasureCategoryId::generate());

        return $measure;
    }

    public static function random(): MitigationMeasure
    {
        return self::fromName('measure-' . rand(100, 999));
    }
}
