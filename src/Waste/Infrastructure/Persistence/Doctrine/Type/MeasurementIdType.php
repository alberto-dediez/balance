<?php

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use Doctrine\DBAL\Types\Type;

class MeasurementIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'measurement_id';
    }

    public function getClassName(): string
    {
        return MeasurementId::class;
    }
}
