<?php

namespace App\Waste\Application\Container\PrintAllQr;

use App\Security\Domain\Model\Account\AccountId;

class PrintAllQrContainerRequest
{
    public function __construct(public AccountId $accountId)
    {
        
    }
}