<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MitigationMeasureIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'mitigation_measure_id';
    }

    public function getClassName(): string
    {
        return MitigationMeasureId::class;
    }
}
