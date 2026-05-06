<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;

class SurplusCauseMother
{
    public static function registeredSurplusCause(): SurplusCause
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): SurplusCause
    {
        return self::from(
            name: $name,
            id: (string) SurplusCauseId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): SurplusCause
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): SurplusCause {
        $surplusCause = new SurplusCause(
            id: new SurplusCauseId($id),
        );
        $surplusCause->register($name);

        return $surplusCause;
    }

    public static function random(): SurplusCause
    {
        return self::from(
            name: 'surplusCause-' . random_int(100, 999),
            id: (string) SurplusCauseId::generate(),
        );
    }
}
