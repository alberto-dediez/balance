<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ContestEntryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'contest_entry_id';
    }

    public function getClassName(): string
    {
        return ContestEntryId::class;
    }
}
