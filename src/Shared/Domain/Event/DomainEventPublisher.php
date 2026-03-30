<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

final class DomainEventPublisher
{
    private static ?DomainEventPublisher $instance = null;

    /**
     * @var DomainEventSubscriber[]
     */
    private array $subscribers = [];

    /**
     * @var DomainEvent[]
     */
    private array $queue = [];

    private bool $isLock = false;

    private function __construct() {}

    public static function instance(): DomainEventPublisher
    {
        if (!self::$instance instanceof DomainEventPublisher) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __clone()
    {
        throw new \BadMethodCallException('Clone is not supported');
    }

    public function subscribe(DomainEventSubscriber $subscriber): void
    {
        if (!isset($this->subscribers[$subscriber::class])) {
            $this->subscribers[$subscriber::class] = $subscriber;
        }
    }

    public function reset(): void
    {
        $this->subscribers = [];
    }

    public function publish(DomainEvent $event): void
    {
        if ($this->isLock) {
            $this->queue[] = $event;

            return;
        }

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->isSubscribedTo($event)) {
                $subscriber->handle($event);
            }
        }
    }

    public function lock(): void
    {
        $this->isLock = true;
    }

    public function unlock(): void
    {
        $this->isLock = false;

        foreach ($this->queue as $i => $event) {
            $this->publish($event);

            unset($this->queue[$i]);
        }
    }
}
