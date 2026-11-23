<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

class FakeIntegrationMessageBus implements MessageBusInterface
{
    /** @var list<Envelope> */
    private array $envelopes = [];

    private int $calls = 0;

    public function __construct(private readonly ?int $failOnCall = null) {}

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        ++$this->calls;

        if ($this->calls === $this->failOnCall) {
            throw new TransportException('Broker unreachable');
        }

        $envelope = new Envelope($message, $stamps);
        $this->envelopes[] = $envelope;

        return $envelope;
    }

    /** @return list<Envelope> */
    public function envelopes(): array
    {
        return $this->envelopes;
    }
}
