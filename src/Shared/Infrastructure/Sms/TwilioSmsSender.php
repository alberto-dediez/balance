<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Sms;

use App\Shared\Domain\Model\Sms\SmsSender;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

final class TwilioSmsSender implements SmsSender
{
    public function __construct(
        private readonly TexterInterface $texter,
    ) {}

    public function __invoke(string $to, string $body): void
    {
        $this->texter->send(new SmsMessage($to, $body));
    }
}
