<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;

class MaterialitySectorMother
{
    public static function registeredMaterialitySector(): MaterialitySector
    {
        return self::fromName('materiality-sector-name');
    }

    public static function fromName(string $name): MaterialitySector
    {
        return self::from(
            name: $name,
            id: (string) MaterialitySectorId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): MaterialitySector
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(string $name, string $id): MaterialitySector
    {
        $materialitySector = new MaterialitySector(
            id: new MaterialitySectorId($id),
        );
        $materialitySector->register(
            name: $name,
        );

        return $materialitySector;
    }

    public static function random(): MaterialitySector
    {
        return self::from(
            name: 'materiality-sector-' . rand(100, 999),
            id: (string) MaterialitySectorId::generate(),
        );
    }
}
