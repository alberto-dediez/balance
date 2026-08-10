<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

use App\Shared\Domain\Model\Filter;

class PaymentFilter
{
    use Filter;

    public function __construct(
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?PaymentStatus $status = null,
        public ?string $externalId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
