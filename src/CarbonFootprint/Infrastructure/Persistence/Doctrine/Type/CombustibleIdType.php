<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CombustibleIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'combustible_id';
    }

    public function getClassName(): string
    {
        return CombustibleId::class;
    }
}
