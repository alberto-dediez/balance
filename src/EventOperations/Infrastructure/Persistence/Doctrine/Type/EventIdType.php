<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Persistence\Doctrine\Type;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class EventIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'event_id';
    }

    public function getClassName(): string
    {
        return EventId::class;
    }
}
