<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\List;

class ListRecipientOrganizationsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization> $recipientOrganizations */
    public function __construct(public array $recipientOrganizations = [], public int $totalResults = 0) {}
}
