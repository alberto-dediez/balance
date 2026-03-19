<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Doctrine\DBAL\Types\Type;

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
