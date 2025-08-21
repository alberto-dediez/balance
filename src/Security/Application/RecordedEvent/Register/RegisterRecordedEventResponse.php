<?php

namespace App\Security\Application\RecordedEvent\Register;

use App\Shared\Domain\Event\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;

class RegisterRecordedEventResponse implements CommandResponse
{
    public function __construct(public RecordedEventId $recordedEventId, public ?UserId $userId)
    {
        
    }
}