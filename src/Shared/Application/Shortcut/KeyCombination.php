<?php

declare(strict_types=1);

namespace App\Shared\Application\Shortcut;

final readonly class KeyCombination
{
    public string $key;

    public function __construct(
        string $key,
        public bool $ctrl = false,
        public bool $alt = false,
        public bool $shift = false,
    ) {
        $key = mb_strtolower($key);

        if ('' === $key) {
            throw new \InvalidArgumentException('The key of a shortcut cannot be empty');
        }

        if (mb_strlen($key) > 1 && 1 !== preg_match('/^(f([1-9]|1[0-2])|enter)$/', $key)) {
            throw new \InvalidArgumentException(sprintf('The key "%s" is not supported', $key));
        }

        $this->key = $key;
    }

    public static function key(string $key): self
    {
        return new self($key);
    }

    public static function ctrl(string $key): self
    {
        return new self($key, ctrl: true);
    }

    public static function shift(string $key): self
    {
        return new self($key, shift: true);
    }

    public function hasModifier(): bool
    {
        return $this->ctrl || $this->alt || $this->shift;
    }

    public function isGlobal(): bool
    {
        return $this->ctrl || $this->alt;
    }

    public function id(): string
    {
        return implode('+', [...$this->modifiers(['ctrl', 'alt', 'shift']), $this->key]);
    }

    public function label(): string
    {
        $key = 1 === mb_strlen($this->key) ? mb_strtoupper($this->key) : ucfirst($this->key);

        return implode('+', [...$this->modifiers(['Ctrl', 'Alt', 'Shift']), $key]);
    }

    /**
     * @param array{string, string, string} $names
     *
     * @return list<string>
     */
    private function modifiers(array $names): array
    {
        return array_values(array_filter(
            [$this->ctrl ? $names[0] : null, $this->alt ? $names[1] : null, $this->shift ? $names[2] : null],
            fn(?string $name): bool => null !== $name,
        ));
    }
}
