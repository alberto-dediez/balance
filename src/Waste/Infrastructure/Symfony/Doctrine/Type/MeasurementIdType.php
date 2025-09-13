<?php

namespace App\Waste\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;

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
