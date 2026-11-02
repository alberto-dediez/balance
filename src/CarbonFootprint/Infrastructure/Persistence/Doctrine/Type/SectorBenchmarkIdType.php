<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class SectorBenchmarkIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'sector_benchmark_id';
    }

    public function getClassName(): string
    {
        return SectorBenchmarkId::class;
    }
}
