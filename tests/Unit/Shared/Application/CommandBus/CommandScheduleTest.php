<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Assistant\Application\Conversation\ExpireStale\ExpireStaleAssistantWorkRequest;
use App\Assistant\Application\Conversation\Purge\PurgeConversationsRequest;
use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsRequest;
use App\Library\Application\Media\SyncStorage\SyncMediaStorageRequest;
use App\Shared\Application\CommandBus\CommandSchedule;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\ScheduledCommand;
use App\Shared\Application\CommandBus\TimedCommand;
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

    public function testSynchronizesTheMediaStorageEveryFiveMinutes(): void
    {
        $scheduled = array_values(array_filter(
            (new CommandSchedule())->commands(),
            static fn(ScheduledCommand $command): bool => 'library:sync-media-storage' === $command->id(),
        ));

        $this->assertCount(1, $scheduled);
        $this->assertSame(5, $scheduled[0]->intervalInMinutes());

        $command = $scheduled[0]->command();

        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(SyncMediaStorageRequest::class, $command->command());
    }

    public function testPurgesAssistantConversationsDaily(): void
    {
        $scheduled = array_values(array_filter(
            (new CommandSchedule())->commands(),
            static fn(ScheduledCommand $command): bool => 'assistant:purge-conversations' === $command->id(),
        ));

        $this->assertCount(1, $scheduled);
        $this->assertSame('daily at 03:20 UTC', $scheduled[0]->describe());

        $command = $scheduled[0]->command();

        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(PurgeConversationsRequest::class, $command->command());
    }

    public function testExpiresStaleAssistantWorkEveryFiveMinutes(): void
    {
        $scheduled = array_values(array_filter(
            (new CommandSchedule())->commands(),
            static fn(ScheduledCommand $command): bool => 'assistant:expire-stale-work' === $command->id(),
        ));

        $this->assertCount(1, $scheduled);
        $this->assertSame(5, $scheduled[0]->intervalInMinutes());

        $command = $scheduled[0]->command();

        $this->assertInstanceOf(DeferredCommand::class, $command);
        $this->assertInstanceOf(ExpireStaleAssistantWorkRequest::class, $command->command());
    }

    public function testEveryScheduledCommandReceivesTheExecutionTime(): void
    {
        foreach ((new CommandSchedule())->commands() as $scheduled) {
            $command = $scheduled->command();

            $this->assertInstanceOf(DeferredCommand::class, $command);
            $this->assertInstanceOf(TimedCommand::class, $command->command(), $scheduled->id());
        }
    }
}
