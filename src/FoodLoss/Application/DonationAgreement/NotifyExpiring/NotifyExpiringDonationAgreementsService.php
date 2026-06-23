<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\NotifyExpiring;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class NotifyExpiringDonationAgreementsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly DonationAgreementRepository $donationAgreementRepository,
        private readonly UserRepository $userRepository,
        private readonly DonationAgreementExpirationNotifier $notifier,
    ) {}

    public function __invoke(NotifyExpiringDonationAgreementsRequest $request, User $user): NotifyExpiringDonationAgreementsResponse
    {
        $donationAgreements = $this->donationAgreementRepository->findExpiringSoon(
            DonationAgreement::EXPIRING_SOON_DAYS,
        );

        $notified = 0;

        foreach ($donationAgreements as $donationAgreement) {
            $this->notify($donationAgreement);
            ++$notified;
        }

        return new NotifyExpiringDonationAgreementsResponse(notified: $notified);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof NotifyExpiringDonationAgreementsRequest;
    }

    private function notify(DonationAgreement $donationAgreement): void
    {
        $accountId = $donationAgreement->accountId();

        if (null === $accountId) {
            return;
        }

        $owners = array_filter(
            $this->userRepository->findByAccountId($accountId),
            static fn(User $user): bool => $user->hasRole(Role::Owner) && !$user->isRemoved(),
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
