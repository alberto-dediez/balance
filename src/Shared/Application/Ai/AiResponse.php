<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

final readonly class AiResponse
{
    /** @param array<mixed> $data */
    public function __construct(
        public array $data,
        public AiUsage $usage,
        public string $stopReason = 'end_turn',
    ) {}
}
