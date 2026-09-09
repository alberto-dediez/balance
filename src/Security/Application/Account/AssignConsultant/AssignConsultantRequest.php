<?php

declare(strict_types=1);

namespace App\Security\Application\Account\AssignConsultant;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class AssignConsultantRequest implements Command
{
    public function __construct(public AccountId $accountId, public ?AccountId $consultantAccountId = null) {}
}
