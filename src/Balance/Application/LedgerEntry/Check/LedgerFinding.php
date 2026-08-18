<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Check;

final class LedgerFinding
{
    public function __construct(
        public readonly LedgerFindingCode $code,
        public readonly string $subjectId,
        public readonly string $detail,
        public readonly bool $repaired = false,
    ) {}

    public function repaired(): self
    {
        return new self($this->code, $this->subjectId, $this->detail, true);
    }
}
