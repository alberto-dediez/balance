<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MachineryFuelIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'machinery_fuel_id';
    }

    public function getClassName(): string
    {
        return MachineryFuelId::class;
    }
}
