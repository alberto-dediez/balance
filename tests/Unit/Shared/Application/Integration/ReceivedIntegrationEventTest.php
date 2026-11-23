<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Integration;

use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use App\Tests\Unit\Shared\Infrastructure\Integration\ReceivedIntegrationEventMother;
use PHPUnit\Framework\TestCase;

class ReceivedIntegrationEventTest extends TestCase
{
    public function testReadsTypedPayloadFields(): void
    {
        $event = ReceivedIntegrationEventMother::withPayload([
            'name' => 'Hotel',
            'nickname' => null,
            'alias' => 'H',
            'rooms' => 120,
            'active' => false,
            'tags' => ['a', 'b'],
        ]);

        $this->assertSame('Hotel', $event->string('name'));
        $this->assertNull($event->nullableString('nickname'));
        $this->assertSame('H', $event->nullableString('alias'));
        $this->assertSame(120, $event->int('rooms'));
        $this->assertFalse($event->bool('active'));
        $this->assertSame(['a', 'b'], $event->array('tags'));
    }

    /** @return iterable<string, array{string}> */
    public static function accessors(): iterable
    {
        foreach (['string', 'nullableString', 'int', 'bool', 'array'] as $accessor) {
            yield $accessor => [$accessor];
        }
    }

    /** @dataProvider accessors */
    public function testMissingFieldThrows(string $accessor): void
    {
        $event = ReceivedIntegrationEventMother::withPayload([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload field "field" is missing');

        $this->read($event, $accessor, 'field');
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function wrongTypes(): iterable
    {
        yield 'string from int' => ['string', 1];
        yield 'string from null' => ['string', null];
        yield 'nullableString from int' => ['nullableString', 1];
        yield 'int from string' => ['int', '1'];
        yield 'int from float' => ['int', 1.5];
        yield 'bool from int' => ['bool', 1];
        yield 'bool from string' => ['bool', 'true'];
        yield 'array from string' => ['array', 'a'];
    }

    /** @dataProvider wrongTypes */
    public function testWrongTypeThrows(string $accessor, mixed $value): void
    {
        $event = ReceivedIntegrationEventMother::withPayload(['field' => $value]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload field "field" of integration event "platform.pinged" must be');

        $this->read($event, $accessor, 'field');
    }

    private function read(ReceivedIntegrationEvent $event, string $accessor, string $key): mixed
    {
        return match ($accessor) {
            'string' => $event->string($key),
            'nullableString' => $event->nullableString($key),
            'int' => $event->int($key),
            'bool' => $event->bool($key),
            'array' => $event->array($key),
            default => throw new \LogicException(sprintf('Unknown accessor "%s"', $accessor)),
        };
    }
}
