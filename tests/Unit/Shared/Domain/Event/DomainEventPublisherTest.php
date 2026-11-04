<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

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

    public function testUnlockDoesNotPublishTwiceAnEventWhoseSubscriberFailed(): void
    {
        $publisher = DomainEventPublisher::instance();
        $publisher->reset();

        $failing = new FailingOnceDomainEventSubscriber();
        $failing->register();

        $publisher->lock();
        $publisher->publish($first = new FakeEvent());
        $publisher->publish($second = new FakeEvent());

        try {
            $publisher->unlock();
            $this->fail('The failing subscriber should have thrown');
        } catch (\RuntimeException) {
        }

        $publisher->unlock();

        $this->assertSame([$first, $second], $failing->events());
    }
}
