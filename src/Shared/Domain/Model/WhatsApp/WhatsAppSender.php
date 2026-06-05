<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\WhatsApp;

interface WhatsAppSender
{
    public function __invoke(string $to, string $body): void;
}
