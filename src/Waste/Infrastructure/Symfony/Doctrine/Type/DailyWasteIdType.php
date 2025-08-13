<?php

namespace App\Waste\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

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