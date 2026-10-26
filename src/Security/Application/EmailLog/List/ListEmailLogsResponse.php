<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\List;

use App\Security\Domain\Model\EmailLog\EmailLog;

class ListEmailLogsResponse
{
    /** @param array<EmailLog> $emailLogs */
    public function __construct(public array $emailLogs = [], public int $totalResults = 0) {}
}
