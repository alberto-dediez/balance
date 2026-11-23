<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Console;

use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandSchedule;
use App\Shared\Application\CommandBus\ScheduledCommand;
use App\Shared\Domain\Model\CommandBus\ScheduledCommandRecord;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryScheduledCommandRecordRepository;
use App\Shared\Infrastructure\UI\Console\RunScheduledCommandsCommand;
use App\Tests\Unit\Notifications\Application\Notification\Send\SpyDeferredCommandHandler;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\FakeScheduledInnerCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunScheduledCommandsCommandTest extends TestCase
{
    private InMemoryScheduledCommandRecordRepository $records;
    private SpyDeferredCommandHandler $spy;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();

        $this->records = new InMemoryScheduledCommandRecordRepository();
        $this->spy = new SpyDeferredCommandHandler();
        $this->spy->register();
    }

    public function testTimedTaskWithoutRecordIsRegisteredWithoutDispatching(): void
    {
        $tester = $this->tester(ScheduledCommand::dailyAt('daily', new FakeScheduledInnerCommand(), '03:20'));

        $this->assertSame(Command::SUCCESS, $tester->execute(['username' => 'admin']));
        $this->assertCount(0, $this->spy->commands());

        $record = $this->records->findByCommandId('daily');
        $this->assertNotNull($record);
        $this->assertEqualsWithDelta(time(), $record->lastRunAt()->getTimestamp(), 5);
    }

    public function testIntervalTaskWithoutRecordIsDispatchedAndRegistered(): void
    {
        $tester = $this->tester(ScheduledCommand::everyMinutes('interval', new FakeScheduledInnerCommand(), 5));

        $tester->execute(['username' => 'admin']);

        $this->assertCount(1, $this->spy->commands());
        $this->assertNotNull($this->records->findByCommandId('interval'));
    }

    public function testTimedTaskRanMoreThanADayAgoIsDispatched(): void
    {
        $lastRunAt = new \DateTimeImmutable('-25 hours');
        $this->records->save(new ScheduledCommandRecord('daily', $lastRunAt));
        $tester = $this->tester(ScheduledCommand::dailyAt('daily', new FakeScheduledInnerCommand(), '03:20'));

        $tester->execute(['username' => 'admin']);

        $this->assertCount(1, $this->spy->commands());
        $this->assertGreaterThan($lastRunAt, $this->records->findByCommandId('daily')?->lastRunAt());
    }

    public function testFailsWhenTheUserDoesNotExist(): void
    {
        $tester = $this->tester(ScheduledCommand::everyMinutes('interval', new FakeScheduledInnerCommand(), 5));

        $this->assertSame(Command::FAILURE, $tester->execute(['username' => 'ghost']));
        $this->assertCount(0, $this->spy->commands());
    }

    private function tester(ScheduledCommand ...$tasks): CommandTester
    {
        $schedule = new class ($tasks) extends CommandSchedule {
            /** @param ScheduledCommand[] $tasks */
            public function __construct(private readonly array $tasks) {}

            public function commands(): array
            {
                return $this->tasks;
            }
        };

        $users = new InMemoryUserRepository();
        $admin = UserMother::fromUsernameAndRole('admin', Role::Admin);
        $users->add($admin);

        return new CommandTester(new RunScheduledCommandsCommand($schedule, $this->records, $users, new NullLogger()));
    }
}
