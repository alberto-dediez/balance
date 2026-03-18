<?php

declare(strict_types=1);

namespace App\Security\Application\RecordedEvent\Register;

use App\Security\Domain\Model\RecordedEvent\RecordedEventId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterRecordedEventResponse implements CommandResponse
{
    public function __construct(public RecordedEventId $recordedEventId, public ?UserId $userId) {}
}
