<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyGroups
{
    /** @param array<string, LegacyGroupAssignment> $assignments */
    public function __construct(public array $assignments = []) {}

    public static function empty(): self
    {
        return new self();
    }

    public function assignment(string $organizationId): ?LegacyGroupAssignment
    {
        return $this->assignments[$organizationId] ?? null;
    }

    public function groupOf(string $organizationId): ?string
    {
        return $this->assignment($organizationId)?->group;
    }
}
