<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class PublicLandingIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'public_landing_id';
    }

    public function getClassName(): string
    {
        return PublicLandingId::class;
    }
}
