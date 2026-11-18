<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolResult;

final readonly class ToolOutcome
{
    public function __construct(
        public ?ToolArguments $arguments,
        public ?ToolResult $result,
        public ?string $error,
        public int $durationMs,
    ) {}

    public static function succeeded(ToolArguments $arguments, ToolResult $result, int $durationMs): self
    {
        return new self($arguments, $result, null, $durationMs);
    }

    public static function failed(string $message, ?ToolArguments $arguments = null, int $durationMs = 0): self
    {
        return new self($arguments, null, $message, $durationMs);
    }

    public function isSuccessful(): bool
    {
        return null !== $this->result;
    }

    /** @return array<string, list<string>> */
    public function argumentValues(): array
    {
        return $this->arguments?->all() ?? [];
    }
}
