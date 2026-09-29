<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MitigationMeasureCategoryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'mitigation_measure_category_id';
    }

    public function getClassName(): string
    {
        return MitigationMeasureCategoryId::class;
    }
}
