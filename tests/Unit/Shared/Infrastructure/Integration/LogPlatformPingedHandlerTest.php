<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Infrastructure\Integration\LogPlatformPingedHandler;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use PHPUnit\Framework\TestCase;

class LogPlatformPingedHandlerTest extends TestCase
{
    public function testSupportsOnlyPlatformPingedVersionOne(): void
    {
        $sut = new LogPlatformPingedHandler(new SpyLogger());

        $this->assertTrue($sut->supports('platform.pinged', 1));
        $this->assertFalse($sut->supports('platform.pinged', 2));
        $this->assertFalse($sut->supports('property.registered', 1));
    }

    public function testHandleLogsTheMessage(): void
    {
        $logger = new SpyLogger();

        (new LogPlatformPingedHandler($logger))->handle(ReceivedIntegrationEventMother::platformPinged(), UserMother::adminUser());

        $this->assertSame(['info'], $logger->levels());
    }
}
