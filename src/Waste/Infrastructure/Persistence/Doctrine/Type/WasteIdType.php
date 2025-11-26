<?php

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\Waste\WasteId;
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
