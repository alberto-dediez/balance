<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Register;

use App\Security\Domain\Model\EmailLog\EmailLogStatus;

class RegisterEmailLogRequest
{
    public function __construct(
        public string $emailLogId,
        public string $recipientEmail,
        public string $recipientName,
        public string $subject,
        public string $template,
        public string $provider,
        public ?string $providerMessageId,
        public EmailLogStatus $status,
        public ?string $statusReason,
        public \DateTimeImmutable $sentAt,
    ) {}
}
