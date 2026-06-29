<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayEmissionFactorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class OvernightStayEmissionFactorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'overnight_stay_emission_factor_id';
    }

    public function getClassName(): string
    {
        return OvernightStayEmissionFactorId::class;
    }
}
