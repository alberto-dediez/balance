<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ElectricityMixIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'electricity_mix_id';
    }

    public function getClassName(): string
    {
        return ElectricityMixId::class;
    }
}
