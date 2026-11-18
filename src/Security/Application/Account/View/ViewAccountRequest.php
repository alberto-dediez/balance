<?php

declare(strict_types=1);

namespace App\Security\Application\Account\View;

use App\Security\Domain\Model\Account\AccountId;

class ViewAccountRequest
{
    public function __construct(public AccountId $accountId) {}
}
