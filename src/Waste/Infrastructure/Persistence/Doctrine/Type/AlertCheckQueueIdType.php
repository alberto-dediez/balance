<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;
use Doctrine\DBAL\Types\Type;

class AlertCheckQueueIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'alert_check_queue_id';
    }

    public function getClassName(): string
    {
        return AlertCheckQueueId::class;
    }
}
