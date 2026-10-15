<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PurchaseServiceIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'purchase_service_id';
    }

    public function getClassName(): string
    {
        return PurchaseServiceId::class;
    }
}
