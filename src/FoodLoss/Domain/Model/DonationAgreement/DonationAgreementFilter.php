<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class DonationAgreementFilter
{
    use Filter;

    public function __construct(
        public ?RecipientOrganizationId $recipientOrganizationId = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
