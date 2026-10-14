<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Deliver;

use App\Shared\Domain\Model\Email\EmailAttachment;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;

class SpyEmailSender implements EmailSender
{
    /** @var list<array{recipient: EmailRecipient, subject: string, template: string, params: ?array<string,mixed>, attachments: list<EmailAttachment>}> */
    private array $sent = [];

    public function __construct(private readonly bool $shouldFail = false) {}

    /**
     * @param array<string,mixed>|null $params
     * @param list<EmailAttachment>    $attachments
     */
    public function __invoke(
        EmailRecipient $recipient,
        string $subject,
        string $template,
        ?array $params = null,
        array $attachments = [],
    ): void {
        if ($this->shouldFail) {
            throw new \RuntimeException('email transport failure');
        }

        $this->sent[] = [
            'recipient' => $recipient,
            'subject' => $subject,
            'template' => $template,
            'params' => $params,
            'attachments' => $attachments,
        ];
    }

    /** @return list<array{recipient: EmailRecipient, subject: string, template: string, params: ?array<string,mixed>, attachments: list<EmailAttachment>}> */
    public function sent(): array
    {
        return $this->sent;
    }
}
