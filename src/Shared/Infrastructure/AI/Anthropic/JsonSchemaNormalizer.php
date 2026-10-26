<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Anthropic;

final class JsonSchemaNormalizer
{
    private const array UNSUPPORTED_KEYS = [
        'minimum',
        'maximum',
        'exclusiveMinimum',
        'exclusiveMaximum',
        'multipleOf',
        'minLength',
        'maxLength',
        'minItems',
        'maxItems',
        'pattern',
        'propertyOrdering',
        'nullable',
    ];

    private const array SUPPORTED_FORMATS = ['date-time', 'time', 'date', 'duration', 'email', 'hostname', 'uri', 'ipv4', 'ipv6', 'uuid'];

    /**
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    public function normalize(array $schema): array
    {
        if (isset($schema['type'])) {
            $schema['type'] = $this->type($schema['type']);
        }

        if (true === ($schema['nullable'] ?? false) && is_string($schema['type'] ?? null)) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            $schema['properties'] = array_map(fn(mixed $property): mixed => is_array($property) ? $this->normalize($property) : $property, $schema['properties']);
        }

        if ($this->isObject($schema)) {
            $schema['additionalProperties'] = false;
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = array_is_list($schema['items'])
                ? $this->normalizeList($schema['items'])
                : $this->normalize($schema['items']);
        }

        if (isset($schema['oneOf']) && !isset($schema['anyOf'])) {
            $schema['anyOf'] = $schema['oneOf'];
            unset($schema['oneOf']);
        }

        foreach (['anyOf', 'allOf', '$defs', 'definitions'] as $key) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                $schema[$key] = $this->normalizeList($schema[$key]);
            }
        }

        foreach (self::UNSUPPORTED_KEYS as $key) {
            unset($schema[$key]);
        }

        if (isset($schema['format']) && !in_array($schema['format'], self::SUPPORTED_FORMATS, true)) {
            unset($schema['format']);
        }

        return $schema;
    }

    /**
     * @param array<mixed> $schemas
     *
     * @return array<mixed>
     */
    private function normalizeList(array $schemas): array
    {
        return array_map(fn(mixed $schema): mixed => is_array($schema) ? $this->normalize($schema) : $schema, $schemas);
    }

    private function type(mixed $type): mixed
    {
        if (is_string($type)) {
            return strtolower($type);
        }

        if (is_array($type)) {
            return array_map(fn(mixed $item): mixed => is_string($item) ? strtolower($item) : $item, $type);
        }

        return $type;
    }

    /**
     * @param array<mixed> $schema
     */
    private function isObject(array $schema): bool
    {
        $type = $schema['type'] ?? null;

        return 'object' === $type
            || (is_array($type) && in_array('object', $type, true))
            || isset($schema['properties']);
    }
}
