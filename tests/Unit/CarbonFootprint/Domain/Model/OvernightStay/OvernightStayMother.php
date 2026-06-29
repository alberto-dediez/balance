<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;

class OvernightStayMother
{
    public static function registeredOvernightStay(): OvernightStay
    {
        return self::fromName('overnight-stay-name');
    }

    public static function fromName(string $name): OvernightStay
    {
        return self::from(
            name: $name,
            id: (string) OvernightStayId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): OvernightStay
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): OvernightStay {
        $overnightStay = new OvernightStay(
            id: new OvernightStayId($id),
        );
        $overnightStay->register($name);

        return $overnightStay;
    }

    public static function random(): OvernightStay
    {
        return self::from(
            name: 'overnight-stay-' . rand(100, 999),
            id: (string) OvernightStayId::generate(),
        );
    }
}
