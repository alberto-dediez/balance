<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\Register;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;

final class RegisterContactRequestResponse
{
    public function __construct(
        public readonly ContactRequest $contactRequest,
        public readonly bool $created,
    ) {}
}
