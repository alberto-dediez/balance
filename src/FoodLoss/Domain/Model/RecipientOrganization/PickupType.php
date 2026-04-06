<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

enum PickupType: string
{
    case Pickup = 'Pickup';

    case DeliveryToDestination = 'DeliveryToDestination';
}
