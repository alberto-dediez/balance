<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

enum RecipientOrganizationType: string
{
    case FoodBank = 'FoodBank';

    case Ngo = 'Ngo';

    case SocialDining = 'SocialDining';

    case WelfareEntity = 'WelfareEntity';

    case DigitalDonationPlatform = 'DigitalDonationPlatform';
}
