<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProtocolIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'protocol_id';
    }

    public function getClassName(): string
    {
        return ProtocolId::class;
    }
}
