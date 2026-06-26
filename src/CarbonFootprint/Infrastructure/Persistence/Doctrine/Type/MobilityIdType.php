<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MobilityIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'mobility_id';
    }

    public function getClassName(): string
    {
        return MobilityId::class;
    }
}
