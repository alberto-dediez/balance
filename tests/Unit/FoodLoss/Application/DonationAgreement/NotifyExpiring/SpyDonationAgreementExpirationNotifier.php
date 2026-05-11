<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\DonationAgreementExpirationNotifier;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\Security\Domain\Model\User\User;

final class SpyDonationAgreementExpirationNotifier implements DonationAgreementExpirationNotifier
{
    /** @var array<int, array{agreement: DonationAgreement, owner: User}> */
    public array $calls = [];

    public function notify(DonationAgreement $donationAgreement, User $owner): void
    {
        $this->calls[] = ['agreement' => $donationAgreement, 'owner' => $owner];
    }
}
