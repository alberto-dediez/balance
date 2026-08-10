<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Shared\Application\CommandBus\CommandResponse;

class ExpirePendingContributionsResponse implements CommandResponse
{
    public function __construct(public readonly int $expired = 0, public readonly int $confirmed = 0) {}
}
