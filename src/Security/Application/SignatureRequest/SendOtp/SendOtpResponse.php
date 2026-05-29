<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\SendOtp;

use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;

class SendOtpResponse
{
    public function __construct(
        public SignatureRequestId $signatureRequestId,
        public NotificationChannel $notificationChannel,
        public string $maskedDestination,
        public bool $alreadyVerified,
    ) {}
}
