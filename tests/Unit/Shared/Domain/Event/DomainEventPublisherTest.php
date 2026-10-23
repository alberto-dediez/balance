<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\AggregateRootId;
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

final class FailingOnceDomainEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    private bool $failed = false;

    /** @var DomainEvent[] */
    private array $events = [];

    public function handle(DomainEvent $event): void
    {
        $this->events[] = $event;

        if (!$this->failed) {
            $this->failed = true;

            throw new \RuntimeException('Subscriber failed');
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return true;
    }

    /** @return DomainEvent[] */
    public function events(): array
    {
        return $this->events;
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
    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new FakeEvent($id, $occurredOn);
    }
}
