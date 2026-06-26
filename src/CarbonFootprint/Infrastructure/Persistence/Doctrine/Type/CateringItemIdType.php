<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CateringItemIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'catering_item_id';
    }

    public function getClassName(): string
    {
        return CateringItemId::class;
    }
}
