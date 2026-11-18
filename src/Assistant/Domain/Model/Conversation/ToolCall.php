<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ToolCall
{
    public const int TOOL_MAX_LENGTH = 64;
    public const int RESULT_MAX_LENGTH = 2000;

    /** @param Collection<int, ToolCallArgument> $arguments */
    public function __construct(
        private ToolCallId $id,
        private Message $message,
        private int $position,
        private string $tool,
        private string $summary,
        private bool $failed,
        private int $durationMs,
        private ?string $result = null,
        private Collection $arguments = new ArrayCollection(),
    ) {}

    /** @param array<string, list<string>> $arguments */
    public static function record(ToolCallId $id, Message $message, int $position, string $tool, array $arguments, string $summary, bool $failed, int $durationMs, ?string $result = null): self
    {
        $call = new self($id, $message, $position, mb_substr($tool, 0, self::TOOL_MAX_LENGTH), $summary, $failed, $durationMs, null === $result ? null : mb_substr($result, 0, self::RESULT_MAX_LENGTH));
        $index = 0;

        foreach ($arguments as $name => $values) {
            foreach ($values as $value) {
                $call->arguments->add(new ToolCallArgument($call, mb_substr((string) $name, 0, ToolCallArgument::NAME_MAX_LENGTH), ++$index, $value));
            }
        }

        return $call;
    }

    public function id(): ToolCallId
    {
        return $this->id;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function tool(): string
    {
        return $this->tool;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function failed(): bool
    {
        return $this->failed;
    }

    public function durationMs(): int
    {
        return $this->durationMs;
    }

    public function result(): ?string
    {
        return $this->result;
    }

    /** @return list<ToolCallArgument> */
    public function arguments(): array
    {
        $arguments = array_values($this->arguments->toArray());

        usort($arguments, static fn(ToolCallArgument $a, ToolCallArgument $b): int => $a->position() <=> $b->position());

        return $arguments;
    }

    /** @return array<string, list<string>> */
    public function argumentValues(): array
    {
        $values = [];

        foreach ($this->arguments() as $argument) {
            $values[$argument->name()][] = $argument->value();
        }

        return $values;
    }
}
