<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProcessIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'process_id';
    }

    public function getClassName(): string
    {
        return ProcessId::class;
    }
}
