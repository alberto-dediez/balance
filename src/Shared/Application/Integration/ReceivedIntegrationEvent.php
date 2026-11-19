<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration;

final readonly class ReceivedIntegrationEvent
{
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

    public function string(string $key): string
    {
        $value = $this->value($key);

        if (!is_string($value)) {
            throw $this->invalidType($key, 'string');
        }

        return $value;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->value($key);

        if (null !== $value && !is_string($value)) {
            throw $this->invalidType($key, 'string or null');
        }

        return $value;
    }

    public function int(string $key): int
    {
        $value = $this->value($key);

        if (!is_int($value)) {
            throw $this->invalidType($key, 'int');
        }

        return $value;
    }

    public function bool(string $key): bool
    {
        $value = $this->value($key);

        if (!is_bool($value)) {
            throw $this->invalidType($key, 'bool');
        }

        return $value;
    }

    /** @return array<mixed> */
    public function array(string $key): array
    {
        $value = $this->value($key);

        if (!is_array($value)) {
            throw $this->invalidType($key, 'array');
        }

        return $value;
    }

    private function value(string $key): mixed
    {
        if (!array_key_exists($key, $this->payload)) {
            throw new \InvalidArgumentException(sprintf('Payload field "%s" is missing in integration event "%s"', $key, $this->type));
        }

        return $this->payload[$key];
    }

    private function invalidType(string $key, string $expected): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf('Payload field "%s" of integration event "%s" must be %s', $key, $this->type, $expected));
    }
}
