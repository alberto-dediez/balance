<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\Register;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterRecipientOrganizationResponse implements CommandResponse
{
    public function __construct(
        public RecipientOrganizationId $recipientOrganizationId,
        public string $name,
    ) {}
}
