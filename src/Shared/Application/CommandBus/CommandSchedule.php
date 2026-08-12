<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsRequest;
use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsRequest;
use App\Security\Application\SignatureRequest\PurgeExpired\PurgeExpiredSignatureRequestsRequest;

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
            ScheduledCommand::everyHours(
                id: 'balance:expire-pending-contributions',
                command: new ExpirePendingContributionsRequest(new \DateTimeImmutable('NOW')),
                hours: 1,
            ),
        ];
    }
}
