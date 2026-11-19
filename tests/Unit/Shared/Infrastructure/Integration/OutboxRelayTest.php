<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Infrastructure\Integration\Messenger\OutgoingIntegrationMessage;
use App\Shared\Infrastructure\Integration\OutboxRelay;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Tests\Unit\Shared\Domain\Model\Integration\IntegrationOutboxMessageMother;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;

class OutboxRelayTest extends TestCase
{
    public function testPublishesInSequenceOrderWithRoutingKeyAndMarksSent(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $first = IntegrationOutboxMessageMother::from(type: 'platform.pinged');
        $second = IntegrationOutboxMessageMother::from(type: 'property.registered', version: 2);
        $outbox->add($first);
        $outbox->add($second);
        $bus = new FakeIntegrationMessageBus();

        $sent = $this->createSUT($outbox, $bus)->relay(100);

        $this->assertSame(2, $sent);
        $envelopes = $bus->envelopes();
        $this->assertCount(2, $envelopes);

        $firstMessage = $envelopes[0]->getMessage();
        $this->assertInstanceOf(OutgoingIntegrationMessage::class, $firstMessage);
        $this->assertSame($first->messageId(), $firstMessage->envelope->id);
        $firstStamp = $envelopes[0]->last(AmqpStamp::class);
        $this->assertInstanceOf(AmqpStamp::class, $firstStamp);
        $this->assertSame('platform.pinged.v1', $firstStamp->getRoutingKey());
        $this->assertSame('application/json', $firstStamp->getAttributes()['content_type'] ?? null);
        $secondStamp = $envelopes[1]->last(AmqpStamp::class);
        $this->assertInstanceOf(AmqpStamp::class, $secondStamp);
        $this->assertSame('property.registered.v2', $secondStamp->getRoutingKey());

        $this->assertTrue($first->isSent());
        $this->assertTrue($second->isSent());
        $this->assertSame([], $outbox->pending(100));
    }

    public function testFailureStopsTheBatchAndRecordsTheAttempt(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $first = IntegrationOutboxMessageMother::pending();
        $second = IntegrationOutboxMessageMother::pending();
        $third = IntegrationOutboxMessageMother::pending();
        $outbox->add($first);
        $outbox->add($second);
        $outbox->add($third);
        $logger = new SpyLogger();

        $sent = $this->createSUT($outbox, new FakeIntegrationMessageBus(failOnCall: 2), $logger)->relay(100);

        $this->assertSame(1, $sent);
        $this->assertTrue($first->isSent());
        $this->assertFalse($second->isSent());
        $this->assertSame(1, $second->attempts());
        $this->assertSame('Broker unreachable', $second->lastError());
        $this->assertFalse($third->isSent());
        $this->assertSame(0, $third->attempts());
        $this->assertSame(['error'], $logger->levels());
    }

    public function testEnvelopeCarriesThePlatformAsSource(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $outbox->add(IntegrationOutboxMessageMother::pending());
        $bus = new FakeIntegrationMessageBus();

        $this->createSUT($outbox, $bus)->relay(100);

        $message = $bus->envelopes()[0]->getMessage();
        $this->assertInstanceOf(OutgoingIntegrationMessage::class, $message);
        $this->assertSame('waste', $message->envelope->source);
    }

    private function createSUT(
        InMemoryIntegrationOutboxRepository $outbox,
        FakeIntegrationMessageBus $bus,
        ?SpyLogger $logger = null,
    ): OutboxRelay {
        return new OutboxRelay($outbox, $bus, IntegrationSettingsMother::enabled('waste'), $logger ?? new SpyLogger());
    }
}
