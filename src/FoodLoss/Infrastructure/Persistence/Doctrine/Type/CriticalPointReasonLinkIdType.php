<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointReasonLinkId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CriticalPointReasonLinkIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'critical_point_reason_link_id';
    }

    public function getClassName(): string
    {
        return CriticalPointReasonLinkId::class;
    }
}
