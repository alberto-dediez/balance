<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Shared\Domain\Model\Locale\Locale;

class Signer
{
    public function __construct(
        private readonly string $name = '',
        private readonly Locale $locale = Locale::Spanish,
        private readonly ?string $email = null,
        private readonly ?string $phone = null,
        private readonly NotificationChannel $notificationChannel = NotificationChannel::Email,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function notificationChannel(): NotificationChannel
    {
        return $this->notificationChannel;
    }
}
