<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class UndecodableIntegrationMessageHandler
{
    private const int MAX_BODY_LENGTH = 2000;

    public function __construct(private readonly LoggerInterface $logger) {}

    public function __invoke(UndecodableIntegrationMessage $message): void
    {
        $this->logger->error('Integration message could not be decoded', [
            'reason' => $message->reason,
            'body' => mb_substr($message->body, 0, self::MAX_BODY_LENGTH),
        ]);

        throw new UnrecoverableMessageHandlingException($message->reason);
    }
}
