<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Contest\ContestHostId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ContestHostIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'contest_host_id';
    }

    public function getClassName(): string
    {
        return ContestHostId::class;
    }
}
