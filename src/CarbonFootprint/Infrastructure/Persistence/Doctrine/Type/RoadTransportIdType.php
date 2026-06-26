<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class RoadTransportIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'road_transport_id';
    }

    public function getClassName(): string
    {
        return RoadTransportId::class;
    }
}
