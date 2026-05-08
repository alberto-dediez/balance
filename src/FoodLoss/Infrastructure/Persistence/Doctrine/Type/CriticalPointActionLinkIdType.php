<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointActionLinkId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class CriticalPointActionLinkIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'critical_point_action_link_id';
    }

    public function getClassName(): string
    {
        return CriticalPointActionLinkId::class;
    }
}
