<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Application\Integration\Ping\PlatformPinged;
use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Infrastructure\Integration\BufferedIntegrationEventPublisher;
use App\Shared\Infrastructure\Integration\FlushIntegrationOutboxSubscriber;
use App\Shared\Infrastructure\Integration\IntegrationContext;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FlushIntegrationOutboxSubscriberTest extends TestCase
{
    private InMemoryIntegrationOutboxRepository $outbox;
    private BufferedIntegrationEventPublisher $publisher;
    private FlushIntegrationOutboxSubscriber $sut;

    protected function setUp(): void
    {
        $this->outbox = new InMemoryIntegrationOutboxRepository();
        $this->publisher = new BufferedIntegrationEventPublisher(
            IntegrationSettingsMother::enabled(),
            new IntegrationContext(),
            $this->outbox,
            new SpyLogger(),
        );
        $this->sut = new FlushIntegrationOutboxSubscriber($this->publisher);
        $this->publisher->publish(new PlatformPinged('hola'));
    }

    public function testKernelTerminateWithoutExceptionWrites(): void
    {
        $this->sut->flushAndDiscard();

        $this->assertSame(1, $this->outboxCount());
    }

    public function testKernelExceptionAndTerminateDoesNotWrite(): void
    {
        $this->sut->markFailed();
        $this->sut->flushAndDiscard();

        $this->assertSame(0, $this->outboxCount());
    }

    public function testConsoleTerminateWithSuccessWrites(): void
    {
        $this->sut->onConsoleTerminate($this->consoleTerminate(0));

        $this->assertSame(1, $this->outboxCount());
    }

    public function testConsoleTerminateWithFailureDiscards(): void
    {
        $this->sut->onConsoleTerminate($this->consoleTerminate(1));
        $this->publisher->flush();

        $this->assertSame(0, $this->outboxCount());
    }

    public function testConsoleErrorAndTerminateWithSuccessDoesNotWrite(): void
    {
        $this->sut->markFailed();
        $this->sut->onConsoleTerminate($this->consoleTerminate(0));

        $this->assertSame(0, $this->outboxCount());
    }

    public function testWorkerMessageHandledWrites(): void
    {
        $this->sut->flush();

        $this->assertSame(1, $this->outboxCount());
    }

    public function testWorkerMessageFailedDiscards(): void
    {
        $this->sut->discard();
        $this->sut->flush();

        $this->assertSame(0, $this->outboxCount());
    }

    private function consoleTerminate(int $exitCode): ConsoleTerminateEvent
    {
        return new ConsoleTerminateEvent(new Command('test'), new ArrayInput([]), new NullOutput(), $exitCode);
    }

    private function outboxCount(): int
    {
        return count($this->outbox->list(new IntegrationOutboxFilter(limit: 0)));
    }
}
