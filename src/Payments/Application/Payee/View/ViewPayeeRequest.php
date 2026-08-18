<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\View;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeType;

final class ViewPayeeRequest
{
    public function __construct(
        public readonly ?PayeeId $payeeId = null,
        public readonly ?PayeeType $type = null,
        public readonly ?string $subjectId = null,
    ) {}
}
