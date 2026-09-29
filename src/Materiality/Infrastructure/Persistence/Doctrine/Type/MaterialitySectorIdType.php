<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Type;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MaterialitySectorIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'materiality_sector_id';
    }

    public function getClassName(): string
    {
        return MaterialitySectorId::class;
    }
}
