<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;

class UseProfileMother
{
    public static function registeredUseProfile(): UseProfile
    {
        return self::fromName('use-profile-name');
    }

    public static function fromName(string $name): UseProfile
    {
        return self::from(
            name: $name,
            id: (string) UseProfileId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): UseProfile
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        Unit $unit = Unit::Piece,
    ): UseProfile {
        $useProfile = new UseProfile(
            id: new UseProfileId($id),
        );
        $useProfile->register($name, $unit);

        return $useProfile;
    }

    public static function random(): UseProfile
    {
        return self::from(
            name: 'use-profile-' . rand(100, 999),
            id: (string) UseProfileId::generate(),
        );
    }
}
