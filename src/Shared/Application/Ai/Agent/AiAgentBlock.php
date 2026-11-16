<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

use App\Shared\Application\Ai\AiAttachment;

final readonly class AiAgentBlock
{
    public const string TEXT = 'text';
    public const string TOOL_USE = 'tool_use';
    public const string TOOL_RESULT = 'tool_result';
    public const string ATTACHMENT = 'attachment';
    public const string OPAQUE = 'opaque';

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $raw
     * @param list<AiAttachment>   $attachments
     */
    private function __construct(
        public string $type,
        public string $text = '',
        public string $id = '',
        public string $name = '',
        public array $input = [],
        public bool $isError = false,
        public array $raw = [],
        public ?AiAttachment $attachment = null,
        public array $attachments = [],
    ) {}

    public static function text(string $text): self
    {
        return new self(self::TEXT, text: $text);
    }

    /** @param array<string, mixed> $input */
    public static function toolUse(string $id, string $name, array $input): self
    {
        return new self(self::TOOL_USE, id: $id, name: $name, input: $input);
    }

    /** @param list<AiAttachment> $attachments */
    public static function toolResult(string $toolUseId, string $content, bool $isError = false, array $attachments = []): self
    {
        return new self(self::TOOL_RESULT, text: $content, id: $toolUseId, isError: $isError, attachments: $attachments);
    }

    public static function attachment(AiAttachment $attachment): self
    {
        return new self(self::ATTACHMENT, attachment: $attachment);
    }

    /** @param array<string, mixed> $raw */
    public static function opaque(array $raw): self
    {
        return new self(self::OPAQUE, raw: $raw);
    }

    public function isText(): bool
    {
        return self::TEXT === $this->type;
    }

    public function isToolUse(): bool
    {
        return self::TOOL_USE === $this->type;
    }

    public function isToolResult(): bool
    {
        return self::TOOL_RESULT === $this->type;
    }

    public function isAttachment(): bool
    {
        return self::ATTACHMENT === $this->type;
    }

    public function isOpaque(): bool
    {
        return self::OPAQUE === $this->type;
    }
}
