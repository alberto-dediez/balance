<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Register;

use App\Security\Domain\Model\EmailLog\EmailLogId;

class RegisterEmailLogResponse
{
    public function __construct(public EmailLogId $emailLogId) {}
}
