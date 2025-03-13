<?php

namespace App\Security\Application\RecordedEvent\Register;

use App\Shared\Domain\Event\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;

class RegisterRecordedEventResponse
{
    public function __construct(public RecordedEventId $recordedEventId, public ?UserId $userId)
    {
        
    }
}