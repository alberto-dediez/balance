<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Application\Integration\Ping\PlatformPinged;
use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationOutboxMessage;
use App\Shared\Infrastructure\Integration\BufferedIntegrationEventPublisher;
use App\Shared\Infrastructure\Integration\IntegrationContext;
use App\Shared\Infrastructure\Integration\IntegrationSettings;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use PHPUnit\Framework\TestCase;

class BufferedIntegrationEventPublisherTest extends TestCase
{
    private InMemoryIntegrationOutboxRepository $outbox;
    private IntegrationContext $context;
    private SpyLogger $logger;

    protected function setUp(): void
    {
        $this->outbox = new InMemoryIntegrationOutboxRepository();
        $this->context = new IntegrationContext();
        $this->logger = new SpyLogger();
    }

    public function testDisabledDoesNotBuffer(): void
    {
        $sut = $this->createSUT(IntegrationSettingsMother::disabled());

        $sut->publish(new PlatformPinged('hola'));
        $sut->flush();

        $this->assertSame([], $this->outboxMessages());
    }

    public function testWithoutContextStartsANewCorrelation(): void
    {
        $sut = $this->createSUT();

        $sut->publish(new PlatformPinged('hola'));
        $sut->flush();

        $messages = $this->outboxMessages();
        $this->assertCount(1, $messages);
        $this->assertSame($messages[0]->messageId(), $messages[0]->correlationId());
        $this->assertNull($messages[0]->causationId());
        $this->assertSame(0, $messages[0]->hops());
        $this->assertSame('platform.pinged', $messages[0]->type());
        $this->assertSame(1, $messages[0]->version());
        $this->assertSame('{"message":"hola"}', $messages[0]->payload());
    }

    public function testWithContextInheritsCorrelationAndIncrementsHops(): void
    {
        $incoming = ReceivedIntegrationEventMother::from(hops: 2);
        $this->context->enter($incoming);
        $sut = $this->createSUT();

        $sut->publish(new PlatformPinged('hola'));
        $sut->flush();

        $messages = $this->outboxMessages();
        $this->assertCount(1, $messages);
        $this->assertSame($incoming->correlationId, $messages[0]->correlationId());
        $this->assertSame($incoming->id, $messages[0]->causationId());
        $this->assertSame(3, $messages[0]->hops());
    }

    public function testAboveMaxHopsIsNotBufferedAndLogsWarning(): void
    {
        $this->context->enter(ReceivedIntegrationEventMother::from(hops: 5));
        $sut = $this->createSUT(IntegrationSettingsMother::enabled(maxHops: 5));

        $sut->publish(new PlatformPinged('hola'));
        $sut->flush();

        $this->assertSame([], $this->outboxMessages());
        $this->assertSame(['warning'], $this->logger->levels());
    }

    public function testFlushWritesInOrderAndEmptiesTheBuffer(): void
    {
        $sut = $this->createSUT();

        $sut->publish(new PlatformPinged('first'));
        $sut->publish(new PlatformPinged('second'));
        $sut->flush();
        $sut->flush();

        $messages = $this->outboxMessages();
        $this->assertCount(2, $messages);
        $this->assertSame('{"message":"first"}', $messages[0]->payload());
        $this->assertSame('{"message":"second"}', $messages[1]->payload());
        $this->assertLessThan($messages[1]->sequence(), $messages[0]->sequence());
    }

    public function testMarkFailedPreventsFlushFromWriting(): void
    {
        $sut = $this->createSUT();

        $sut->publish(new PlatformPinged('hola'));
        $sut->markFailed();
        $sut->flush();

        $this->assertSame([], $this->outboxMessages());

        $sut->publish(new PlatformPinged('after'));
        $sut->flush();

        $this->assertCount(1, $this->outboxMessages());
    }

    public function testDiscardEmptiesTheBuffer(): void
    {
        $sut = $this->createSUT();

        $sut->publish(new PlatformPinged('hola'));
        $sut->discard();
        $sut->flush();

        $this->assertSame([], $this->outboxMessages());
    }

    public function testResetEmptiesTheBuffer(): void
    {
        $sut = $this->createSUT();

        $sut->publish(new PlatformPinged('hola'));
        $sut->markFailed();
        $sut->reset();
        $sut->publish(new PlatformPinged('after'));
        $sut->flush();

        $messages = $this->outboxMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('{"message":"after"}', $messages[0]->payload());
    }

    private function createSUT(?IntegrationSettings $settings = null): BufferedIntegrationEventPublisher
    {
        return new BufferedIntegrationEventPublisher(
            $settings ?? IntegrationSettingsMother::enabled(),
            $this->context,
            $this->outbox,
            $this->logger,
        );
    }

    /** @return list<IntegrationOutboxMessage> */
    private function outboxMessages(): array
    {
        $filter = new IntegrationOutboxFilter(limit: 0);
        $filter->orderWay = 'ASC';

        return $this->outbox->list($filter);
    }
}
