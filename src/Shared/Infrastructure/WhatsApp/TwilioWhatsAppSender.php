<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\WhatsApp;

use App\Shared\Domain\Model\WhatsApp\WhatsAppSender;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

final class TwilioWhatsAppSender implements WhatsAppSender
{
    public function __construct(
        private readonly TexterInterface $texter,
    ) {}

    public function __invoke(string $to, string $body): void
    {
        // Twilio WhatsApp requires the "whatsapp:" URI prefix on the destination
        $this->texter->send(new SmsMessage('whatsapp:' . $to, $body));
    }
}
