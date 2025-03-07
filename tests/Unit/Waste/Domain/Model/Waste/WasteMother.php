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
        $accountId = WasteId::generate();

        $account = new Waste($accountId);
        $account->register($name, true);

        return $account;
    }

    public static function fromNameAndId(string $name, string $id)
    {
        $account = new Waste(
            id: new WasteId($id)
        );
        $account->register($name, true);

        return $account;
    }

    public static function random()
    {
        return self::fromName('waste-' . rand(100, 999));
    }
}