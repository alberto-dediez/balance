<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Track;

class TrackEmailEventRequest
{
    public function __construct(
        public string $emailLogId,
        public EmailTrackingEventType $type,
        public \DateTimeImmutable $occurredOn,
        public ?string $reason = null,
    ) {}
}
