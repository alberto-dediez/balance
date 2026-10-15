<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceEmissionFactorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PurchaseServiceEmissionFactorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'purchase_service_emission_factor_id';
    }

    public function getClassName(): string
    {
        return PurchaseServiceEmissionFactorId::class;
    }
}
