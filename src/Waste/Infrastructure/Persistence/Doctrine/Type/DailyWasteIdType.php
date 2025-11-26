<?php

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use Doctrine\DBAL\Types\Type;

class DailyWasteIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'daily_waste_id';
    }

    public function getClassName(): string
    {
        return DailyWasteId::class;
    }
}
