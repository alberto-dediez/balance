<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Request;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RequestCreditPurchaseRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public ProjectId $projectId,
        public float $tonnes,
        public string $legalName = '',
        public string $taxId = '',
        public string $street = '',
        public string $postalCode = '',
        public string $city = '',
        public string $country = '',
        public string $email = '',
        public bool $termsAccepted = false,
        public ?float $acceptedTotal = null,
    ) {}
}
