<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TransportIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'transport_id';
    }

    public function getClassName(): string
    {
        return TransportId::class;
    }
}
