<?php

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class RecordedEventIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'recorded_event_id';
    }

    /** @return class-string<AggregateRootId> */
    public function getClassName(): string
    {
        return RecordedEventId::class;
    }
}
