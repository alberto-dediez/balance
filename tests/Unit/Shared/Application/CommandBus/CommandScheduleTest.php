<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\CarbonFootprint\Application\ExchangeRate\Sync\SyncExchangeRatesRequest;
use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsRequest;
use App\Shared\Application\CommandBus\CommandSchedule;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\ScheduledCommand;
use PHPUnit\Framework\TestCase;

class CommandScheduleTest extends TestCase
{
    public function testPublishesScheduledAnnouncementsEveryFiveMinutes(): void
    {
        $scheduled = array_values(array_filter(
            (new CommandSchedule())->commands(),
            static fn(ScheduledCommand $command): bool => 'changelog:publish-scheduled-announcements' === $command->id(),
        ));

        $this->assertCount(1, $scheduled);
        $this->assertSame(5, $scheduled[0]->intervalInMinutes());

        $command = $scheduled[0]->command();

        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(PublishScheduledAnnouncementsRequest::class, $command->command());
    }

    public function testSynchronizesExchangeRatesDailyAtFivePm(): void
    {
        $scheduled = array_values(array_filter(
            (new CommandSchedule())->commands(),
            static fn(ScheduledCommand $command): bool => 'carbon-footprint:sync-exchange-rates' === $command->id(),
        ));

        $this->assertCount(1, $scheduled);
        $this->assertSame(24 * 60, $scheduled[0]->intervalInMinutes());
        $this->assertSame('daily at 17:00 UTC', $scheduled[0]->describe());

        $command = $scheduled[0]->command();

        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(SyncExchangeRatesRequest::class, $command->command());
    }
}
