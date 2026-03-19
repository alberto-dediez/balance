<?php

namespace App\Tests\Unit\Property\Domain\Model\Brand;

use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;

class BrandMother
{
    public static function fromName(string $name)
    {
        return self::from(
            brandId: BrandId::generate(),
            name: $name
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            brandId: new BrandId($id),
            name: $name
        );
    }

    public static function from(BrandId $brandId, string $name = 'name'): Brand
    {
        $brand = new Brand(
            id: $brandId
        );

        $brand->register($name);

        return $brand;
    }

    public static function random()
    {
        return self::from(
            brandId: BrandId::generate(),
            name: 'brand-' . random_int(100, 999)
        );
    }
}