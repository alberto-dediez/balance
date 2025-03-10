<?php

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;

class WasteMother
{
    public static function registeredWaste()
    {   
        return self::fromName('name');
    }

    public static function fromName($name)
    {
        $wasteId = WasteId::generate();

        $waste = new Waste($wasteId);
        $waste->register($name);

        return $waste;
    }

    public static function fromNameAndId(string $name, string $id)
    {
        $waste = new Waste(
            id: new WasteId($id)
        );
        $waste->register($name);

        return $waste;
    }

    public static function random()
    {
        return self::fromName('waste-' . rand(100, 999));
    }
}