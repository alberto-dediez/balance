<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\DonationAgreementExpirationNotifier;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use Symfony\Contracts\Translation\TranslatorInterface;

class DonationAgreementExpirationWarningMailer implements DonationAgreementExpirationNotifier
{
    public function __construct(
        private readonly EmailSender $emailSender,
        private readonly TranslatorInterface $translator,
    ) {}

    public function notify(DonationAgreement $donationAgreement, User $owner): void
    {
        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $owner->username(), email: $owner->username()),
            subject: $this->translator->trans('Acuerdo de donación próximo a caducar'),
            template: '@food_loss/donation_agreement/expiration-warning.email.html.twig',
            params: [
                'donationAgreement' => $donationAgreement,
                'owner' => $owner,
            ],
        );
    }
}
