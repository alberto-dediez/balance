<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileEmissionFactorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class UseProfileEmissionFactorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'use_profile_emission_factor_id';
    }

    public function getClassName(): string
    {
        return UseProfileEmissionFactorId::class;
    }
}
