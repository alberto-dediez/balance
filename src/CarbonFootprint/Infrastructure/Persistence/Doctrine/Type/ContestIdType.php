<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ContestIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'contest_id';
    }

    public function getClassName(): string
    {
        return ContestId::class;
    }
}
