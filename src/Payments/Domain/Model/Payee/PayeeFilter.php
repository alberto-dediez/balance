<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

use App\Shared\Domain\Model\Filter;

class PayeeFilter
{
    use Filter;

    public function __construct(
        public ?PayeeType $type = null,
        public ?string $subjectId = null,
        public ?PayeeStatus $status = null,
        public ?string $search = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
