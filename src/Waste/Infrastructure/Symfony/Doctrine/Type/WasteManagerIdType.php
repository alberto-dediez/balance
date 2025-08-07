<?php

namespace App\Waste\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class WasteManagerIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'waste_manager_id';
    }

    public function getClassName(): string
    {
        return WasteManagerId::class;
    }
}