<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\List;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;

class ListRecipientOrganizationsResponse
{
    /** @param array<RecipientOrganization> $recipientOrganizations */
    public function __construct(public array $recipientOrganizations = [], public int $totalResults = 0) {}
}
