<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\DonationAgreementExpirationNotifier;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class DonationAgreementExpirationWarningNotifier implements DonationAgreementExpirationNotifier
{
    public function __construct(
        private readonly SystemNotifier $notifier,
    ) {}

    public function notify(DonationAgreement $donationAgreement, User $owner): void
    {
        $subject = (string) new TranslatableMessage('Acuerdo de donación próximo a caducar');
        $params = [
            'donationAgreement' => $donationAgreement,
            'owner' => $owner,
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $owner->id(),
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@food_loss/donation_agreement/expiration-warning.notification.html.twig',
            params: $params,
            accountId: $owner->accountId(),
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $owner->id(),
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@food_loss/donation_agreement/expiration-warning.email.html.twig',
            params: $params,
            accountId: $owner->accountId(),
        ));
    }
}
