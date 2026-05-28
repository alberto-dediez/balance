<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class RegisterSignatureRequestRequest implements Command
{
    public function __construct(
        public string $documentToSignPath = '',
        public string $signerName = '',
        public Locale $signerLocale = Locale::Spanish,
        public ?string $signerEmail = null,
        public ?string $signerPhone = null,
        public NotificationChannel $notificationChannel = NotificationChannel::Email,
        public ?AccountId $accountId = null,
    ) {}
}
