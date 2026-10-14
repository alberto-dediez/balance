<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Type;

use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class DestinationIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'destination_id';
    }

    public function getClassName(): string
    {
        return DestinationId::class;
    }
}
