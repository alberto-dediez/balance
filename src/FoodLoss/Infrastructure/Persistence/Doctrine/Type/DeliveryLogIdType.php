<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class DeliveryLogIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'delivery_log_id';
    }

    public function getClassName(): string
    {
        return DeliveryLogId::class;
    }
}
