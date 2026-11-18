<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final readonly class ToolParameter
{
    /** @param list<string> $enumValues */
    public function __construct(
        public string $name,
        public string $label,
        public string $description,
        public ToolParameterType $type,
        public bool $required = false,
        public bool $multiple = false,
        public array $enumValues = [],
        public ?int $maxLength = null,
        public int|float|null $min = null,
        public int|float|null $max = null,
    ) {}

    public static function string(string $name, string $label, string $description, ?int $maxLength = 255): self
    {
        return new self($name, $label, $description, ToolParameterType::String, maxLength: $maxLength);
    }

    public static function text(string $name, string $label, string $description, ?int $maxLength = null): self
    {
        return new self($name, $label, $description, ToolParameterType::Text, maxLength: $maxLength);
    }

    public static function integer(string $name, string $label, string $description, ?int $min = null, ?int $max = null): self
    {
        return new self($name, $label, $description, ToolParameterType::Integer, min: $min, max: $max);
    }

    public static function decimal(string $name, string $label, string $description, int|float|null $min = null, int|float|null $max = null): self
    {
        return new self($name, $label, $description, ToolParameterType::Decimal, min: $min, max: $max);
    }

    public static function boolean(string $name, string $label, string $description): self
    {
        return new self($name, $label, $description, ToolParameterType::Boolean);
    }

    public static function date(string $name, string $label, string $description): self
    {
        return new self($name, $label, $description, ToolParameterType::Date);
    }

    public static function dateTime(string $name, string $label, string $description): self
    {
        return new self($name, $label, $description, ToolParameterType::DateTime);
    }

    /** @param list<string> $values */
    public static function enum(string $name, string $label, string $description, array $values): self
    {
        return new self($name, $label, $description, ToolParameterType::Enum, enumValues: $values);
    }

    public static function id(string $name, string $label, string $description): self
    {
        return new self($name, $label, $description, ToolParameterType::Id);
    }

    public static function limit(): self
    {
        return self::integer('limit', 'Límite', 'Número máximo de resultados (por defecto 20, máximo 50).', 1, 50);
    }

    public function required(): self
    {
        return new self($this->name, $this->label, $this->description, $this->type, true, $this->multiple, $this->enumValues, $this->maxLength, $this->min, $this->max);
    }

    public function multiple(): self
    {
        return new self($this->name, $this->label, $this->description, $this->type, $this->required, true, $this->enumValues, $this->maxLength, $this->min, $this->max);
    }
}
