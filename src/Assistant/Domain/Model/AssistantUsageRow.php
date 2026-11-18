<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model;

use App\Security\Domain\Model\User\UserId;

final readonly class AssistantUsageRow
{
    public function __construct(
        public \DateTimeImmutable $day,
        public ?UserId $userId,
        public ?string $model,
        public int $messages,
        public int $inputTokens,
        public int $outputTokens,
        public string $costUsd,
    ) {}
}
