<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class DeliveryLogFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?RecipientOrganizationId $recipientOrganizationId = null,
        public ?PropertyId $propertyId = null,
        public ?string $productType = null,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
