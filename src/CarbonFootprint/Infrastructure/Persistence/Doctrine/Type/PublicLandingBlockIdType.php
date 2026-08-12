<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingBlockId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PublicLandingBlockIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'public_landing_block_id';
    }

    public function getClassName(): string
    {
        return PublicLandingBlockId::class;
    }
}
