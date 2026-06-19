<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Shared\Domain\Model\Locale\Locale;

class Contact
{
    public function __construct(
        private readonly string $name = '',
        private readonly string $email = '',
        private readonly string $phone = '',
        private readonly ?Locale $preferredLocale = null,
        private readonly NotificationChannel $preferredNotificationChannel = NotificationChannel::Email,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function preferredLocale(): ?Locale
    {
        return $this->preferredLocale;
    }

    public function preferredNotificationChannel(): NotificationChannel
    {
        return $this->preferredNotificationChannel;
    }
}
