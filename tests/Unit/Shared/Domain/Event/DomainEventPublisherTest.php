<?php

namespace App\Tests\Unit\Shared\Domain\Event;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;

class DomainEventPublisherTest extends TestCase
{
    private $spySubscriber;
    private $veryPickySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->veryPickySubscriber = new VeryPickyDomainEventSubscriber();      
    }

    public function testPublishSendMessageToAllSubscribersWhoIsSubscribedToThatMessage()
    {
        $publisher = DomainEventPublisher::instance();

        $this->assertCount(0, $this->spySubscriber->events());
        $this->assertCount(0, $this->veryPickySubscriber->events());  
        
        $publisher->publish(new FakeEvent());

        $this->assertCount(1, $this->spySubscriber->events());
        $this->assertCount(0, $this->veryPickySubscriber->events());
    }

    public function testSubscribeNotAddSameSubscriberTwice()
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

class FakeEvent extends DomainEvent
{
    public function payload(): ?array
    {
        return null;
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, ?array $payload): static
    {
        return new FakeEvent($id, $occurredOn);
    }
}