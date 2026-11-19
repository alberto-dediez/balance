<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Console;

use App\Shared\Infrastructure\Integration\IntegrationSettings;
use App\Shared\Infrastructure\Integration\OutboxRelay;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Shared\Infrastructure\UI\Console\RelayIntegrationOutboxCommand;
use App\Tests\Unit\Shared\Domain\Model\Integration\IntegrationOutboxMessageMother;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use App\Tests\Unit\Shared\Infrastructure\Integration\FakeIntegrationMessageBus;
use App\Tests\Unit\Shared\Infrastructure\Integration\IntegrationSettingsMother;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

class RelayIntegrationOutboxCommandTest extends TestCase
{
    private InMemoryIntegrationOutboxRepository $outbox;

    private FakeIntegrationMessageBus $bus;

    private LockFactory $lockFactory;

    protected function setUp(): void
    {
        $this->outbox = new InMemoryIntegrationOutboxRepository();
        $this->outbox->add(IntegrationOutboxMessageMother::pending());
        $this->bus = new FakeIntegrationMessageBus();
        $this->lockFactory = new LockFactory(new InMemoryStore());
    }

    public function testDisabledWithOnceExitsWithoutPublishing(): void
    {
        $tester = new CommandTester($this->createSUT(IntegrationSettingsMother::disabled()));

        $this->assertSame(Command::SUCCESS, $tester->execute(['--once' => true]));
        $this->assertStringContainsString('Integration events are disabled.', $tester->getDisplay());
        $this->assertSame([], $this->bus->envelopes());
        $this->assertCount(1, $this->outbox->pending(100));
    }

    public function testDisabledWithoutOnceWaitsUntilTheTimeLimit(): void
    {
        $tester = new CommandTester($this->createSUT(IntegrationSettingsMother::disabled()));
        $start = time();

        $this->assertSame(Command::SUCCESS, $tester->execute(['--time-limit' => '1', '--sleep' => '50']));
        $this->assertGreaterThanOrEqual($start + 1, time());
        $this->assertStringContainsString('Integration events are disabled.', $tester->getDisplay());
        $this->assertSame([], $this->bus->envelopes());
    }

    public function testBusyLockDoesNotPublish(): void
    {
        $otherRelay = $this->lockFactory->createLock('integration-outbox-relay');
        $this->assertTrue($otherRelay->acquire());
        $tester = new CommandTester($this->createSUT(IntegrationSettingsMother::enabled()));

        $this->assertSame(Command::SUCCESS, $tester->execute(['--once' => true]));
        $this->assertStringContainsString('Another relay is active.', $tester->getDisplay());
        $this->assertSame([], $this->bus->envelopes());
        $this->assertCount(1, $this->outbox->pending(100));
    }

    public function testOncePublishesOneBatchClearsTheManagerAndReleasesTheLock(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())->method('clear');
        $tester = new CommandTester($this->createSUT(IntegrationSettingsMother::enabled(), $manager));

        $this->assertSame(Command::SUCCESS, $tester->execute(['--once' => true]));
        $this->assertCount(1, $this->bus->envelopes());
        $this->assertSame([], $this->outbox->pending(100));
        $this->assertTrue($this->lockFactory->createLock('integration-outbox-relay')->acquire());
    }

    public function testLockIsReleasedWhenTheRoundFails(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('clear')->willThrowException(new \RuntimeException('Database gone'));
        $tester = new CommandTester($this->createSUT(IntegrationSettingsMother::enabled(), $manager));

        try {
            $tester->execute(['--once' => true]);
            $this->fail('The failure of the round must propagate');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database gone', $exception->getMessage());
        }

        $this->assertTrue($this->lockFactory->createLock('integration-outbox-relay')->acquire());
    }

    private function createSUT(IntegrationSettings $settings, ?EntityManagerInterface $manager = null): RelayIntegrationOutboxCommand
    {
        return new RelayIntegrationOutboxCommand(
            new OutboxRelay($this->outbox, $this->bus, $settings, new SpyLogger()),
            $settings,
            $this->lockFactory,
            $manager ?? $this->createMock(EntityManagerInterface::class),
        );
    }
}
