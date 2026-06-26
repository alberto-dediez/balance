<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ElectricityMarketerIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'electricity_marketer_id';
    }

    public function getClassName(): string
    {
        return ElectricityMarketerId::class;
    }
}
