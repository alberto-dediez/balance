<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class UseProfileIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'use_profile_id';
    }

    public function getClassName(): string
    {
        return UseProfileId::class;
    }
}
