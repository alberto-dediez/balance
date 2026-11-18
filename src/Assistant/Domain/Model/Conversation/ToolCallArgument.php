<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

class ToolCallArgument
{
    public const int NAME_MAX_LENGTH = 64;

    public function __construct(
        private ToolCall $toolCall,
        private string $name,
        private int $position,
        private string $value,
        private ?int $id = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function toolCall(): ToolCall
    {
        return $this->toolCall;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function value(): string
    {
        return $this->value;
    }
}
