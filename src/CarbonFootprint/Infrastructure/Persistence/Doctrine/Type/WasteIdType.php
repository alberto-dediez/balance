<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class WasteIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'waste_id';
    }

    public function getClassName(): string
    {
        return WasteId::class;
    }
}
