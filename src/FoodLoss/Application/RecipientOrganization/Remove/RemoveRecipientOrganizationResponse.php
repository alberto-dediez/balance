<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\Remove;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveRecipientOrganizationResponse implements CommandResponse
{
    public function __construct(public RecipientOrganizationId $recipientOrganizationId) {}
}
