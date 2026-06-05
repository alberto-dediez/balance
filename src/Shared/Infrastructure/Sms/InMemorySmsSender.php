<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Sms;

use App\Shared\Domain\Model\Sms\SmsSender;
use Psr\Log\LoggerInterface;

class InMemorySmsSender implements SmsSender
{
    /** @var array<array{to: string, body: string}> */
    private array $messages = [];

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(string $to, string $body): void
    {
        $this->messages[] = ['to' => $to, 'body' => $body];
        $this->logger->info('SMS (in-memory): to={to} body={body}', ['to' => $to, 'body' => $body]);
    }

    /** @return array<array{to: string, body: string}> */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
