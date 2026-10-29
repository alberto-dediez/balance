<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditRange\Check;

final class CreditRangeFinding
{
    public function __construct(
        public readonly CreditRangeFindingCode $code,
        public readonly string $subjectId,
        public readonly string $detail,
        public readonly bool $repaired = false,
    ) {}

    public function repaired(): self
    {
        return new self($this->code, $this->subjectId, $this->detail, true);
    }
}
