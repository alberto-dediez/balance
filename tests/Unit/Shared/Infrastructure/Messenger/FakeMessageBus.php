<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class FakeMessageBus implements MessageBusInterface
{
    /** @var object[] */
    private array $dispatched = [];

    /** @var list<StampInterface[]> */
    private array $stamps = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;
        $this->stamps[] = $stamps;

        return new Envelope($message, $stamps);
    }

    public function lastDispatched(): ?object
    {
        if ([] === $this->dispatched) {
            return null;
        }

        return $this->dispatched[count($this->dispatched) - 1];
    }

    /**
     * @template T of StampInterface
     *
     * @param class-string<T> $stampClass
     *
     * @return T|null
     */
    public function lastStampOfType(string $stampClass): ?StampInterface
    {
        if ([] === $this->stamps) {
            return null;
        }

        foreach ($this->stamps[count($this->stamps) - 1] as $stamp) {
            if ($stamp instanceof $stampClass) {
                return $stamp;
            }
        }

        return null;
    }
}
