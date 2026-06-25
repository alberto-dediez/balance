<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MaterialIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'material_id';
    }

    public function getClassName(): string
    {
        return MaterialId::class;
    }
}
