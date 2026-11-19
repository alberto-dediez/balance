<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use App\Shared\Domain\Model\Integration\IntegrationOutboxMessage;
use Symfony\Component\Uid\Uuid;

final readonly class IntegrationEnvelope
{
    private const string TYPE_PATTERN = '/^[a-z][a-z0-9-]*(\.[a-z][a-z0-9-]*)+$/';

    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $type,
        public int $version,
        public string $source,
        public int $sequence,
        public \DateTimeImmutable $occurredOn,
        public ?string $aggregateId,
        public ?string $accountRef,
        public string $correlationId,
        public ?string $causationId,
        public int $hops,
        public array $payload,
    ) {}

    public static function fromOutbox(IntegrationOutboxMessage $message, string $source): self
    {
        $sequence = $message->sequence();

        if (null === $sequence) {
            throw new \LogicException(sprintf('Integration outbox message "%s" has no sequence yet', $message->messageId()));
        }

        $payload = json_decode($message->payload(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new \LogicException(sprintf('Integration outbox message "%s" has a payload that is not an object', $message->messageId()));
        }

        return new self(
            id: $message->messageId(),
            type: $message->type(),
            version: $message->version(),
            source: $source,
            sequence: $sequence,
            occurredOn: $message->occurredOn()->setTimezone(new \DateTimeZone('UTC')),
            aggregateId: $message->aggregateId(),
            accountRef: $message->accountRef(),
            correlationId: $message->correlationId(),
            causationId: $message->causationId(),
            hops: $message->hops(),
            payload: $payload,
        );
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $occurredOn = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, self::requiredString($data, 'occurredOn'));

        if (!$occurredOn instanceof \DateTimeImmutable) {
            throw MalformedIntegrationEnvelope::because('"occurredOn" must be an ATOM date');
        }

        $type = self::requiredString($data, 'type');

        if (1 !== preg_match(self::TYPE_PATTERN, $type)) {
            throw MalformedIntegrationEnvelope::because(sprintf('"type" "%s" is not valid', $type));
        }

        $source = self::requiredString($data, 'source');

        if (1 !== preg_match(IntegrationSettings::PLATFORM_PATTERN, $source)) {
            throw MalformedIntegrationEnvelope::because(sprintf('"source" "%s" is not valid', $source));
        }

        return new self(
            id: self::requiredUuid($data, 'id'),
            type: $type,
            version: self::requiredInt($data, 'version', 1),
            source: $source,
            sequence: self::requiredInt($data, 'sequence', 1),
            occurredOn: $occurredOn->setTimezone(new \DateTimeZone('UTC')),
            aggregateId: self::nullableString($data, 'aggregateId'),
            accountRef: self::nullableString($data, 'accountRef'),
            correlationId: self::requiredUuid($data, 'correlationId'),
            causationId: self::nullableUuid($data, 'causationId'),
            hops: self::requiredInt($data, 'hops', 0),
            payload: self::payload($data),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'version' => $this->version,
            'source' => $this->source,
            'sequence' => $this->sequence,
            'occurredOn' => $this->occurredOn->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM),
            'aggregateId' => $this->aggregateId,
            'accountRef' => $this->accountRef,
            'correlationId' => $this->correlationId,
            'causationId' => $this->causationId,
            'hops' => $this->hops,
            'payload' => [] === $this->payload ? new \stdClass() : $this->payload,
        ];
    }

    public function toReceivedEvent(): ReceivedIntegrationEvent
    {
        return new ReceivedIntegrationEvent(
            id: $this->id,
            type: $this->type,
            version: $this->version,
            source: $this->source,
            sequence: $this->sequence,
            occurredOn: $this->occurredOn,
            aggregateId: $this->aggregateId,
            accountRef: $this->accountRef,
            correlationId: $this->correlationId,
            causationId: $this->causationId,
            hops: $this->hops,
            payload: $this->payload,
        );
    }

    public function routingKey(): string
    {
        return sprintf('%s.v%d', $this->type, $this->version);
    }

    /** @param array<mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        if (!array_key_exists($key, $data)) {
            throw MalformedIntegrationEnvelope::because(sprintf('"%s" is missing', $key));
        }

        if (!is_string($data[$key]) || '' === $data[$key]) {
            throw MalformedIntegrationEnvelope::because(sprintf('"%s" must be a non empty string', $key));
        }

        return $data[$key];
    }

    /** @param array<mixed> $data */
    private static function nullableString(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data)) {
            throw MalformedIntegrationEnvelope::because(sprintf('"%s" is missing', $key));
        }

        if (null === $data[$key]) {
            return null;
        }

        return self::requiredString($data, $key);
    }

    /** @param array<mixed> $data */
    private static function requiredUuid(array $data, string $key): string
    {
        $value = self::requiredString($data, $key);

        if (!Uuid::isValid($value)) {
            throw MalformedIntegrationEnvelope::because(sprintf('"%s" must be a UUID', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function nullableUuid(array $data, string $key): ?string
    {
        if (null === self::nullableString($data, $key)) {
            return null;
        }

        return self::requiredUuid($data, $key);
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, string $key, int $min): int
    {
        if (!array_key_exists($key, $data)) {
            throw MalformedIntegrationEnvelope::because(sprintf('"%s" is missing', $key));
        }

        if (!is_int($data[$key]) || $data[$key] < $min) {
            throw MalformedIntegrationEnvelope::because(sprintf('"%s" must be an integer greater than or equal to %d', $key, $min));
        }

        return $data[$key];
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function payload(array $data): array
    {
        if (!array_key_exists('payload', $data)) {
            throw MalformedIntegrationEnvelope::because('"payload" is missing');
        }

        $payload = $data['payload'];

        if (!is_array($payload) || ([] !== $payload && array_is_list($payload))) {
            throw MalformedIntegrationEnvelope::because('"payload" must be an object');
        }

        return $payload;
    }
}
