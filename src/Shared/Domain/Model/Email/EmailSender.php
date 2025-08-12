<?php

namespace App\Shared\Domain\Model\Email;

interface EmailSender
{
    public function __invoke(EmailRecipient $recipient, string $subject, string $template, ?array $params = null): void;
}