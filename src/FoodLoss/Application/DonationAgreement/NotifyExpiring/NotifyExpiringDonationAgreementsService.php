<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;

class NotifyExpiringDonationAgreementsService
{
    public function __construct(
        private readonly DonationAgreementRepository $donationAgreementRepository,
        private readonly UserRepository $userRepository,
        private readonly DonationAgreementExpirationNotifier $notifier,
    ) {}

    public function __invoke(DonationAgreement $donationAgreement): void
    {
        $accountId = $donationAgreement->accountId();

        if (null === $accountId) {
            return;
        }

        $owners = array_filter(
            $this->userRepository->findByAccountId($accountId),
            static fn (User $user): bool => $user->hasRole(Role::Owner) && !$user->isRemoved(),
        );

        if (0 === count($owners)) {
            return;
        }

        foreach ($owners as $owner) {
            $this->notifier->notify($donationAgreement, $owner);
        }

        $donationAgreement->markExpirationWarningNotified();
        $this->donationAgreementRepository->add($donationAgreement);
    }
}
