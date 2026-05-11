<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\Security\Domain\Model\User\User;

interface DonationAgreementExpirationNotifier
{
    public function notify(DonationAgreement $donationAgreement, User $owner): void;
}
