<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final class ToolArgumentsFactory
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    private const string DATE_TIME_PATTERN = '/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2})?$/';

    /**
     * @param list<ToolParameter>  $parameters
     * @param array<string, mixed> $raw
     * @param list<string>         $deferred
     */
    public static function create(array $parameters, array $raw, ToolContext $context, array $deferred = []): ToolArguments
    {
        $byName = [];

        foreach ($parameters as $parameter) {
            $byName[$parameter->name] = $parameter;
        }

        $errors = [];
        $values = [];

        foreach (array_keys($raw) as $name) {
            if (!isset($byName[$name])) {
                $errors[] = sprintf('«%s»: parámetro desconocido.', $name);
            }
        }

        foreach ($byName as $name => $parameter) {
            $isDeferred = in_array($name, $deferred, true);
            $value = $raw[$name] ?? null;

            if ($isDeferred) {
                continue;
            }

            try {
                $normalized = self::normalizeValue($parameter, $value, $context);
            } catch (\InvalidArgumentException $exception) {
                $errors[] = sprintf('«%s»: %s', $name, $exception->getMessage());

                continue;
            }

            if ([] === $normalized) {
                if ($parameter->required) {
                    $errors[] = sprintf('«%s»: es obligatorio.', $name);
                }

                continue;
            }

            $values[$name] = $normalized;
        }

        if ([] !== $errors) {
            throw new InvalidToolArgumentsException($errors);
        }

        return new ToolArguments($values, $context->timezone);
    }

    /** @return list<string> */
    private static function normalizeValue(ToolParameter $parameter, mixed $value, ToolContext $context): array
    {
        if (null === $value) {
            return [];
        }

        if (is_array($value)) {
            if (!$parameter->multiple) {
                throw new \InvalidArgumentException('admite un único valor, no una lista.');
            }

            $normalized = [];

            foreach ($value as $item) {
                $single = self::normalizeSingle($parameter, $item, $context);

                if (null !== $single) {
                    $normalized[] = $single;
                }
            }

            return $normalized;
        }

        $single = self::normalizeSingle($parameter, $value, $context);

        return null === $single ? [] : [$single];
    }

    private static function normalizeSingle(ToolParameter $parameter, mixed $value, ToolContext $context): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            throw new \InvalidArgumentException('debe ser un valor simple.');
        }

        return match ($parameter->type) {
            ToolParameterType::String, ToolParameterType::Text => self::text($parameter, $value),
            ToolParameterType::Integer => self::integer($parameter, $value),
            ToolParameterType::Decimal => self::decimal($parameter, $value),
            ToolParameterType::Boolean => self::boolean($value),
            ToolParameterType::Date => self::date($value),
            ToolParameterType::DateTime => self::dateTime($value, $context),
            ToolParameterType::Enum => self::enum($parameter, $value),
            ToolParameterType::Id => self::id($value),
        };
    }

    private static function text(ToolParameter $parameter, string|int|float|bool $value): ?string
    {
        $text = trim(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);

        if ('' === $text) {
            return null;
        }

        if (null !== $parameter->maxLength && mb_strlen($text) > $parameter->maxLength) {
            throw new \InvalidArgumentException(sprintf('no puede superar los %d caracteres.', $parameter->maxLength));
        }

        return $text;
    }

    private static function integer(ToolParameter $parameter, string|int|float|bool $value): ?string
    {
        if (is_string($value) && '' === trim($value)) {
            return null;
        }

        if (is_bool($value) || false === filter_var(is_string($value) ? trim($value) : $value, FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException('debe ser un número entero.');
        }

        $number = (int) (is_string($value) ? trim($value) : $value);
        self::assertRange($parameter, $number);

        return (string) $number;
    }

    private static function decimal(ToolParameter $parameter, string|int|float|bool $value): ?string
    {
        if (is_bool($value)) {
            throw new \InvalidArgumentException('debe ser un número.');
        }

        $text = str_replace(',', '.', trim((string) $value));

        if ('' === $text) {
            return null;
        }

        if (1 !== preg_match('/^-?\d+(\.\d+)?$/', $text)) {
            throw new \InvalidArgumentException('debe ser un número.');
        }

        self::assertRange($parameter, (float) $text);

        if (str_contains($text, '.')) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        $text = ltrim($text, '0');

        if ('' === $text || str_starts_with($text, '.')) {
            $text = '0' . $text;
        }

        return $text;
    }

    private static function boolean(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'si', 'sí' => '1',
            '0', 'false', 'no' => '0',
            default => throw new \InvalidArgumentException('debe ser verdadero o falso.'),
        };
    }

    private static function date(string|int|float|bool $value): ?string
    {
        $text = trim((string) $value);

        if ('' === $text) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!' . ToolArguments::DATE_FORMAT, $text);

        if (false === $date || $date->format(ToolArguments::DATE_FORMAT) !== $text) {
            throw new \InvalidArgumentException('debe ser una fecha con el formato AAAA-MM-DD.');
        }

        return $text;
    }

    private static function dateTime(string|int|float|bool $value, ToolContext $context): ?string
    {
        $text = trim((string) $value);

        if ('' === $text) {
            return null;
        }

        if (1 !== preg_match(self::DATE_TIME_PATTERN, $text, $matches)) {
            throw new \InvalidArgumentException('debe ser una fecha y hora con el formato AAAA-MM-DD HH:MM.');
        }

        $normalized = $matches[1] . ' ' . $matches[2];
        $date = \DateTimeImmutable::createFromFormat('!' . ToolArguments::DATE_TIME_FORMAT, $normalized, $context->timezone);

        if (false === $date || $date->format(ToolArguments::DATE_TIME_FORMAT) !== $normalized) {
            throw new \InvalidArgumentException('debe ser una fecha y hora con el formato AAAA-MM-DD HH:MM.');
        }

        return $normalized;
    }

    private static function enum(ToolParameter $parameter, string|int|float|bool $value): ?string
    {
        $text = trim((string) $value);

        if ('' === $text) {
            return null;
        }

        if (!in_array($text, $parameter->enumValues, true)) {
            throw new \InvalidArgumentException(sprintf('debe ser uno de: %s.', implode(', ', $parameter->enumValues)));
        }

        return $text;
    }

    private static function id(string|int|float|bool $value): ?string
    {
        $text = strtolower(trim((string) $value));

        if ('' === $text) {
            return null;
        }

        if (1 !== preg_match(self::UUID_PATTERN, $text)) {
            throw new \InvalidArgumentException('debe ser un identificador UUID válido.');
        }

        return $text;
    }

    private static function assertRange(ToolParameter $parameter, int|float $number): void
    {
        if (null !== $parameter->min && $number < $parameter->min) {
            throw new \InvalidArgumentException(sprintf('debe ser como mínimo %s.', $parameter->min));
        }

        if (null !== $parameter->max && $number > $parameter->max) {
            throw new \InvalidArgumentException(sprintf('debe ser como máximo %s.', $parameter->max));
        }
    }
}
