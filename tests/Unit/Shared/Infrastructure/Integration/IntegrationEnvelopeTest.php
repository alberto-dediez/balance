<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Infrastructure\Integration\IntegrationEnvelope;
use App\Shared\Infrastructure\Integration\MalformedIntegrationEnvelope;
use App\Tests\Unit\Shared\Domain\Model\Integration\IntegrationOutboxMessageMother;
use PHPUnit\Framework\TestCase;

class IntegrationEnvelopeTest extends TestCase
{
    public function testRoundTripThroughArray(): void
    {
        $envelope = IntegrationEnvelopeMother::from(occurredOn: new \DateTimeImmutable('2026-11-19T10:15:00+00:00'), aggregateId: 'P1', causationId: '0193a1c2-7f00-7c3e-9b1d-2f6c1a9e4b11', hops: 2);

        $data = json_decode((string) json_encode($envelope->toArray()), true);
        $this->assertIsArray($data);

        $this->assertEquals($envelope, IntegrationEnvelope::fromArray($data));
    }

    public function testToArrayKeepsTheContractKeyOrder(): void
    {
        $this->assertSame(
            ['id', 'type', 'version', 'source', 'sequence', 'occurredOn', 'aggregateId', 'accountRef', 'correlationId', 'causationId', 'hops', 'payload'],
            array_keys(IntegrationEnvelopeMother::platformPinged()->toArray()),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function requiredFields(): iterable
    {
        foreach (['id', 'type', 'version', 'source', 'sequence', 'occurredOn', 'aggregateId', 'accountRef', 'correlationId', 'causationId', 'hops', 'payload'] as $field) {
            yield $field => [$field];
        }
    }

    /** @dataProvider requiredFields */
    public function testMissingFieldIsMalformed(string $field): void
    {
        $data = $this->validData();
        unset($data[$field]);

        $this->expectException(MalformedIntegrationEnvelope::class);

        IntegrationEnvelope::fromArray($data);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function invalidValues(): iterable
    {
        yield 'type without namespace' => ['type', 'pinged'];
        yield 'type with uppercase' => ['type', 'Platform.Pinged'];
        yield 'version zero' => ['version', 0];
        yield 'version as string' => ['version', '1'];
        yield 'source with spaces' => ['source', 'my platform'];
        yield 'sequence zero' => ['sequence', 0];
        yield 'id not uuid' => ['id', 'not-a-uuid'];
        yield 'correlation not uuid' => ['correlationId', 'nope'];
        yield 'occurredOn not atom' => ['occurredOn', '19/11/2026'];
        yield 'hops negative' => ['hops', -1];
        yield 'payload list' => ['payload', ['a', 'b']];
        yield 'payload scalar' => ['payload', 'hola'];
    }

    /** @dataProvider invalidValues */
    public function testInvalidValueIsMalformed(string $field, mixed $value): void
    {
        $data = $this->validData();
        $data[$field] = $value;

        $this->expectException(MalformedIntegrationEnvelope::class);

        IntegrationEnvelope::fromArray($data);
    }

    public function testExtraKeysAreIgnored(): void
    {
        $data = $this->validData();
        $data['unknown'] = 'value';

        $this->assertEquals(IntegrationEnvelopeMother::platformPinged(), IntegrationEnvelope::fromArray($data));
    }

    public function testFromOutboxDecodesPayload(): void
    {
        $message = IntegrationOutboxMessageMother::from(
            sequence: 7,
            aggregateId: 'P1',
            hops: 1,
            occurredOn: new \DateTimeImmutable('2026-11-19 11:15:00', new \DateTimeZone('Europe/Madrid')),
            payload: '{"message":"hola","count":3}',
        );

        $envelope = IntegrationEnvelope::fromOutbox($message, 'biohydra');

        $this->assertSame($message->messageId(), $envelope->id);
        $this->assertSame('biohydra', $envelope->source);
        $this->assertSame(7, $envelope->sequence);
        $this->assertSame('P1', $envelope->aggregateId);
        $this->assertSame(1, $envelope->hops);
        $this->assertSame(['message' => 'hola', 'count' => 3], $envelope->payload);
        $this->assertSame('2026-11-19T10:15:00+00:00', $envelope->toArray()['occurredOn']);
        $this->assertSame('platform.pinged.v1', $envelope->routingKey());
    }

    public function testFromOutboxKeepsTheInstantWhenTheDefaultTimezoneIsNotUtc(): void
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Madrid');

        try {
            $message = IntegrationOutboxMessageMother::from(sequence: 7, occurredOn: new \DateTimeImmutable('2026-11-19 11:15:00'));

            $envelope = IntegrationEnvelope::fromOutbox($message, 'biohydra');

            $this->assertSame((new \DateTimeImmutable('2026-11-19T10:15:00+00:00'))->getTimestamp(), $envelope->occurredOn->getTimestamp());
            $this->assertSame('2026-11-19T10:15:00+00:00', $envelope->toArray()['occurredOn']);
        } finally {
            date_default_timezone_set($defaultTimezone);
        }
    }

    public function testToReceivedEvent(): void
    {
        $envelope = IntegrationEnvelopeMother::platformPinged();

        $event = $envelope->toReceivedEvent();

        $this->assertSame($envelope->id, $event->id);
        $this->assertSame('platform.pinged', $event->type);
        $this->assertSame(1, $event->version);
        $this->assertSame('waste', $event->source);
        $this->assertSame(1234, $event->sequence);
        $this->assertSame($envelope->correlationId, $event->correlationId);
        $this->assertSame('hola', $event->string('message'));
    }

    /** @return array<string, mixed> */
    private function validData(): array
    {
        $data = json_decode((string) file_get_contents(__DIR__ . '/fixtures/platform-pinged-v1.json'), true);
        $this->assertIsArray($data);

        return $data;
    }
}
