<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class EmissionFactorGroupIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'emission_factor_group_id';
    }

    public function getClassName(): string
    {
        return EmissionFactorGroupId::class;
    }
}
