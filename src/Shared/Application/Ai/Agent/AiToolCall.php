<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

final readonly class AiToolCall
{
    /** @param array<string, mixed> $input */
    public function __construct(
        public string $id,
        public string $name,
        public array $input,
    ) {}
}
