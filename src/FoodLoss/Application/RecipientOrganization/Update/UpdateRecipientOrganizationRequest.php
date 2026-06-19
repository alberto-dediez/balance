<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\RecipientOrganization\Update;

use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationType;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class UpdateRecipientOrganizationRequest implements Command
{
    /** @param PickupType[] $pickupTypes */
    public function __construct(
        public RecipientOrganizationId $recipientOrganizationId,
        public string $name = '',
        public RecipientOrganizationType $type = RecipientOrganizationType::FoodBank,
        public string $nif = '',
        public string $socialReason = '',
        public string $address = '',
        public string $contactName = '',
        public string $contactEmail = '',
        public string $contactPhone = '',
        public ?Locale $contactLocale = null,
        public NotificationChannel $contactNotificationChannel = NotificationChannel::Email,
        public string $operatingHours = '',
        public array $pickupTypes = [],
    ) {}
}
