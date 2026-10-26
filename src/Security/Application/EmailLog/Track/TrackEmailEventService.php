<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Track;

use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;

class TrackEmailEventService
{
    public function __construct(
        private readonly EmailLogRepository $repository,
    ) {}

    public function __invoke(TrackEmailEventRequest $request): TrackEmailEventResponse
    {
        $emailLog = $this->repository->find(EmailLogId::fromString($request->emailLogId));

        if (!$emailLog instanceof EmailLog) {
            return new TrackEmailEventResponse(tracked: false);
        }

        $reason = $request->reason ?? '';

        match ($request->type) {
            EmailTrackingEventType::Delivered => $emailLog->markAsDelivered($request->occurredOn),
            EmailTrackingEventType::Deferred => $emailLog->markAsDeferred($reason, $request->occurredOn),
            EmailTrackingEventType::Bounced => $emailLog->markAsBounced($reason, $request->occurredOn),
            EmailTrackingEventType::Dropped => $emailLog->markAsDropped($reason, $request->occurredOn),
            EmailTrackingEventType::Opened => $emailLog->registerOpen($request->occurredOn),
            EmailTrackingEventType::Clicked => $emailLog->registerClick($request->occurredOn),
            EmailTrackingEventType::SpamReported => $emailLog->markAsSpamReported($request->occurredOn),
        };

        $this->repository->add($emailLog);

        return new TrackEmailEventResponse(tracked: true);
    }
}
