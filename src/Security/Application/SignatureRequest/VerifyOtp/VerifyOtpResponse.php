<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\VerifyOtp;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;

class VerifyOtpResponse
{
    public function __construct(
        public bool $verified,
        public bool $locked,
        public int $attemptsLeft,
        public NotificationChannel $notificationChannel = NotificationChannel::Email,
        public string $maskedDestination = '',
    ) {}
}
