<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ProtocolAccessIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'protocol_access_id';
    }

    public function getClassName(): string
    {
        return ProtocolAccessId::class;
    }
}
