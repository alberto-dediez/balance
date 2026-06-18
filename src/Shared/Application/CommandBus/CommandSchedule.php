<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsRequest;

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
        ];
    }
}
