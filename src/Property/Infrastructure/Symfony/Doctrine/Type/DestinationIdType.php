<?php

namespace App\Property\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;

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
