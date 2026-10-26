<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\EmailLog;

use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;

class EmailLogMother
{
    public static function sent(string $recipientEmail = 'recipient@example.com', string $provider = 'sendgrid', ?EmailLogId $id = null): EmailLog
    {
        return self::from(
            id: $id,
            recipientEmail: $recipientEmail,
            provider: $provider,
        );
    }

    public static function failed(string $reason): EmailLog
    {
        return self::from(
            status: EmailLogStatus::Failed,
            statusReason: $reason,
            providerMessageId: null,
        );
    }

    public static function fromId(string $id, string $recipientEmail): EmailLog
    {
        return self::sent($recipientEmail, 'sendgrid', new EmailLogId($id));
    }

    public static function sentAt(\DateTimeImmutable $sentAt): EmailLog
    {
        return self::from(sentAt: $sentAt);
    }

    public static function delivered(): EmailLog
    {
        $emailLog = self::sent();
        $emailLog->markAsDelivered(new \DateTimeImmutable('NOW'));

        return $emailLog;
    }

    public static function from(
        ?EmailLogId $id = null,
        string $recipientEmail = 'recipient@example.com',
        string $recipientName = 'Recipient',
        string $subject = 'Asunto',
        string $template = '@shared/layout/email.html.twig',
        string $provider = 'sendgrid',
        ?string $providerMessageId = 'provider-message-id',
        EmailLogStatus $status = EmailLogStatus::Sent,
        ?string $statusReason = null,
        ?\DateTimeImmutable $sentAt = null,
    ): EmailLog {
        $emailLog = new EmailLog($id ?? EmailLogId::generate());

        $emailLog->register(
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            subject: $subject,
            template: $template,
            provider: $provider,
            providerMessageId: $providerMessageId,
            status: $status,
            statusReason: $statusReason,
            sentAt: $sentAt ?? new \DateTimeImmutable('NOW'),
        );

        return $emailLog;
    }
}
