<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Sms;

interface SmsSender
{
    public function __invoke(string $to, string $body): void;
}
