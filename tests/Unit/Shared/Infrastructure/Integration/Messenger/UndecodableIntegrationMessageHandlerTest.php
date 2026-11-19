<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration\Messenger;

use App\Shared\Infrastructure\Integration\Messenger\UndecodableIntegrationMessage;
use App\Shared\Infrastructure\Integration\Messenger\UndecodableIntegrationMessageHandler;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class UndecodableIntegrationMessageHandlerTest extends TestCase
{
    public function testThrowsUnrecoverableExceptionAndLogsError(): void
    {
        $logger = new SpyLogger();

        try {
            (new UndecodableIntegrationMessageHandler($logger))(new UndecodableIntegrationMessage('{"id":', 'Syntax error'));
            $this->fail('Expected exception was not thrown');
        } catch (UnrecoverableMessageHandlingException $exception) {
            $this->assertSame('Syntax error', $exception->getMessage());
        }

        $this->assertSame(['error'], $logger->levels());
    }
}
