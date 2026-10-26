<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsRequest;
use App\Balance\Application\LedgerEntry\Check\CheckLedgerRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateAllUnderallocatedContributionsRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Sync\SyncBioscoreCatalogsRequest;
use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesRequest;
use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsRequest;
use App\DataExchange\Application\Exportation\Purge\PurgeExpiredExportationsRequest;
use App\Library\Application\Media\SyncStorage\SyncMediaStorageRequest;
use App\Security\Application\EmailLog\Purge\PurgeEmailLogsRequest;
use App\Security\Application\SignatureRequest\PurgeExpired\PurgeExpiredSignatureRequestsRequest;

class CommandSchedule
{
    private const int LEDGER_CHECK_DAYS = 45;

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
            ScheduledCommand::dailyAt(
                id: 'balance:check-ledger',
                command: new CheckLedgerRequest(
                    repair: false,
                    from: new \DateTimeImmutable('-' . self::LEDGER_CHECK_DAYS . ' days'),
                    to: new \DateTimeImmutable('NOW'),
                ),
                time: '03:00',
            ),
            ScheduledCommand::dailyAt(
                id: 'balance:reallocate-contributions',
                command: new ReallocateAllUnderallocatedContributionsRequest(),
                time: '05:00',
            ),
            ScheduledCommand::dailyAt(
                id: 'balance:check-credit-alerts',
                command: new CheckCreditAlertsRequest(now: new \DateTimeImmutable('NOW')),
                time: '04:30',
            ),
            ScheduledCommand::dailyAt(
                id: 'bioscore:sync-catalogs',
                command: new SyncBioscoreCatalogsRequest(now: new \DateTimeImmutable('NOW')),
                time: '06:00',
            ),
            ScheduledCommand::dailyAt(
                id: 'carbon-footprint:sync-exchange-rates',
                command: new SyncExchangeRatesRequest(now: new \DateTimeImmutable('NOW')),
                time: '17:00',
            ),
            ScheduledCommand::dailyAt(
                id: 'security:purge-email-logs',
                command: new PurgeEmailLogsRequest(new \DateTimeImmutable('NOW')),
                time: '03:00',
            ),
            ScheduledCommand::everyMinutes(
                id: 'changelog:publish-scheduled-announcements',
                command: new PublishScheduledAnnouncementsRequest(new \DateTimeImmutable('NOW')),
                minutes: 5,
            ),
            ScheduledCommand::everyMinutes(
                id: 'library:sync-media-storage',
                command: new SyncMediaStorageRequest(new \DateTimeImmutable('NOW')),
                minutes: 5,
            ),
        ];
    }
}
