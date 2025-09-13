<?php

namespace App\Waste\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;

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
