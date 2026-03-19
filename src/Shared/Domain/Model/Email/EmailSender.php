<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Email;

interface EmailSender
{
    /**
     * @param array<string,mixed>|null $params
     */
    public function __invoke(EmailRecipient $recipient, string $subject, string $template, ?array $params = null): void;
}
