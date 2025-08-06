<?php

namespace App\Security\Infrastructure\Symfony\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Shared\Infrastructure\Symfony\Doctrine\Type\AggregateRootIdTypeTrait;

class RecordedEventIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'recorded_event_id';
    }

    public function getClassName(): string
    {
        return RecordedEventId::class;
    }
}