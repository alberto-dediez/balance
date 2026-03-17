<?php

namespace App\Tests\Unit\Shared\Domain\Event;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Event\DomainEventPublisher;

class DomainEventPublisherTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;
    private VeryPickyDomainEventSubscriber $veryPickySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->veryPickySubscriber = new VeryPickyDomainEventSubscriber();
    }

    public function testPublishSendMessageToAllSubscribersWhoIsSubscribedToThatMessage(): void
    {
        $publisher = DomainEventPublisher::instance();

        $this->assertCount(0, $this->spySubscriber->events());
        $this->assertCount(0, $this->veryPickySubscriber->events());

        $publisher->publish(new FakeEvent());

        $this->assertCount(1, $this->spySubscriber->events());
        $this->assertCount(0, $this->veryPickySubscriber->events());
    }

    public function testSubscribeNotAddSameSubscriberTwice(): void
    {
        $anotherSpy = new SpyDomainEventSubscriber();

        $publisher = DomainEventPublisher::instance();

        $this->assertCount(0, $this->spySubscriber->events());
        $this->assertCount(0, $anotherSpy->events());

        $publisher->publish(new FakeEvent());

        $this->assertCount(1, $this->spySubscriber->events());
        $this->assertCount(0, $anotherSpy->events());
    }
}

final class FakeEvent extends DomainEvent
{
    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [];
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /** @param array<string, mixed> $payload */
    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, ?array $payload): static
    {
        return new FakeEvent($id, $occurredOn);
    }
}
