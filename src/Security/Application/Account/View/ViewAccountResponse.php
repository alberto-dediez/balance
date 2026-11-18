<?php

declare(strict_types=1);

namespace App\Security\Application\Account\View;

use App\Security\Domain\Model\Account\Account;

class ViewAccountResponse
{
    public function __construct(public Account $account) {}
}
