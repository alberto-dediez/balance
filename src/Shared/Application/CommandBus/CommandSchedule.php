<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsRequest;
use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsRequest;
use App\Security\Application\SignatureRequest\PurgeExpired\PurgeExpiredSignatureRequestsRequest;

/**
 * Hardcoded registry of commands that must be dispatched periodically.
 *
 * Add one {@see ScheduledCommand} per recurring task. The interval is
 * expressed in minutes and must be a multiple of
 * {@see ScheduledCommand::TICK_IN_MINUTES}. Wrap the inner command in a
 * {@see DeferredCommand} when it should run asynchronously through the
 * message bus instead of inline.
 */
class CommandSchedule
{
    /**
     * @return ScheduledCommand[]
     */
    public function commands(): array
    {
        return [
            ScheduledCommand::dailyAt(
                id: 'data-exchange:purge-expired-exports',
                command: new PurgeExpiredExportationsRequest(new \DateTimeImmutable('NOW')),
                time: '01:00',
            ),
            ScheduledCommand::dailyAt(
                id: 'security:purge-expired-signature-requests',
                command: new PurgeExpiredSignatureRequestsRequest(new \DateTimeImmutable('NOW')),
                time: '02:00',
            ),
            ScheduledCommand::everyMinutes(
                id: 'changelog:publish-scheduled-announcements',
                command: new PublishScheduledAnnouncementsRequest(new \DateTimeImmutable('NOW')),
                minutes: 5,
            ),
        ];
    }
}
