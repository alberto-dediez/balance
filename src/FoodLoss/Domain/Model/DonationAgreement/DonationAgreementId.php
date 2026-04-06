<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DonationAgreement;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class DonationAgreementId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
