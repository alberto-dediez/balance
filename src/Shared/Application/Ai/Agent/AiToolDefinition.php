<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

final readonly class AiToolDefinition
{
    /** @param array<string, mixed> $inputSchema */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema,
    ) {}
}
