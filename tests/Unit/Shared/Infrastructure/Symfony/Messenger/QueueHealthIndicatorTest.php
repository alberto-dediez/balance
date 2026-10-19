<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Infrastructure\Symfony\Messenger\QueueHealthIndicator;
use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class QueueHealthIndicatorTest extends TestCase
{
    public function testReachableTransportIsHealthy(): void
    {
        $result = $this->indicator(['async_low' => fn() => $this->countingReceiver()])->check();

        self::assertSame('queue', $result->name);
        self::assertTrue($result->healthy);
    }

    public function testFailingMessageCountIsUnreachable(): void
    {
        $result = $this->indicator(['async_low' => fn() => $this->countingReceiver(true)])->check();

        self::assertFalse($result->healthy);
        self::assertSame('transport unreachable', $result->detail);
    }

    public function testFailingTransportFactoryIsUnreachable(): void
    {
        $result = $this->indicator(['async_low' => fn() => throw new \RuntimeException('Invalid DSN')])->check();

        self::assertFalse($result->healthy);
        self::assertSame('transport unreachable', $result->detail);
    }

    public function testTransportWithoutMessageCountIsHealthy(): void
    {
        $result = $this->indicator(['async_low' => fn() => new InMemoryTransport()])->check();

        self::assertTrue($result->healthy);
    }

    public function testMissingTransportIsIgnored(): void
    {
        $result = $this->indicator([])->check();

        self::assertTrue($result->healthy);
    }

    public function testEmptyDoctrineQueueIsHealthy(): void
    {
        $dbal = $this->dbalConnection();
        $transport = $this->doctrineTransport($dbal);

        $result = $this->indicator(['async_low' => fn() => $transport], $dbal)->check();

        self::assertTrue($result->healthy);
    }

    public function testMessageWaitingTooLongIsUnhealthy(): void
    {
        $dbal = $this->dbalConnection();
        $transport = $this->doctrineTransport($dbal);
        $transport->send(new Envelope(new \stdClass()));
        $this->delayAvailableAt($dbal, 20);

        $result = $this->indicator(['async_low' => fn() => $transport], $dbal)->check();

        self::assertFalse($result->healthy);
        self::assertSame('messages waiting too long', $result->detail);
    }

    public function testDeliveredMessageIsHealthy(): void
    {
        $dbal = $this->dbalConnection();
        $transport = $this->doctrineTransport($dbal);
        $transport->send(new Envelope(new \stdClass()));
        $this->delayAvailableAt($dbal, 20);
        $dbal->executeStatement('UPDATE messenger_messages SET delivered_at = available_at');

        $result = $this->indicator(['async_low' => fn() => $transport], $dbal)->check();

        self::assertTrue($result->healthy);
    }

    /** @param array<string, callable(): object> $receivers */
    private function indicator(array $receivers, ?DbalConnection $dbal = null): QueueHealthIndicator
    {
        return new QueueHealthIndicator(
            new ServiceLocator($receivers),
            $dbal ?? $this->dbalConnection(),
            new NullLogger(),
            ['async_high' => 'high', 'async' => 'medium', 'async_low' => 'low'],
            15,
        );
    }

    private function countingReceiver(bool $failing = false): MessageCountAwareInterface
    {
        return new class ($failing) implements MessageCountAwareInterface {
            public function __construct(private readonly bool $failing) {}

            public function getMessageCount(): int
            {
                if ($this->failing) {
                    throw new \RuntimeException('Transport down');
                }

                return 0;
            }
        };
    }

    private function dbalConnection(): DbalConnection
    {
        return DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    }

    private function doctrineTransport(DbalConnection $dbal): DoctrineTransport
    {
        $connection = new Connection(['table_name' => 'messenger_messages', 'queue_name' => 'low'], $dbal);
        $connection->setup();

        return new DoctrineTransport($connection, new PhpSerializer());
    }

    private function delayAvailableAt(DbalConnection $dbal, int $minutes): void
    {
        $availableAt = (new \DateTimeImmutable('UTC'))->modify(sprintf('-%d minutes', $minutes));

        $dbal->executeStatement('UPDATE messenger_messages SET available_at = :availableAt', ['availableAt' => $availableAt->format('Y-m-d H:i:s')]);
    }
}
