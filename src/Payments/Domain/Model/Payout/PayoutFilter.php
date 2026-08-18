<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Shared\Domain\Model\Filter;

class PayoutFilter
{
    use Filter;

    /** @param list<PayeeId>|null $payeeIds */
    public function __construct(
        public ?PayeeId $payeeId = null,
        public ?array $payeeIds = null,
        public ?PayoutStatus $status = null,
        public ?string $number = null,
        public ?string $reference = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
