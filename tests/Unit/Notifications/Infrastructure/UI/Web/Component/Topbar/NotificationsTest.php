<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Infrastructure\UI\Web\Component\Topbar;

use App\Notifications\Application\Notification\Unread\UnreadNotificationsProvider;
use App\Notifications\Infrastructure\UI\Web\Component\Topbar\Notifications;
use PHPUnit\Framework\TestCase;

class NotificationsTest extends TestCase
{
    public function testLiveIsDisabledWhenInternalNotificationsAreDisabled(): void
    {
        $sut = new Notifications(
            $this->createStub(UnreadNotificationsProvider::class),
            internalNotificationsEnabled: false,
            liveNotificationsEnabled: true,
        );

        $this->assertFalse($sut->liveEnabled());
    }

    public function testLiveIsDisabledByFlag(): void
    {
        $sut = new Notifications(
            $this->createStub(UnreadNotificationsProvider::class),
            liveNotificationsEnabled: false,
        );

        $this->assertFalse($sut->liveEnabled());
    }

    public function testLiveIsEnabledByDefault(): void
    {
        $sut = new Notifications($this->createStub(UnreadNotificationsProvider::class));

        $this->assertTrue($sut->liveEnabled());
    }

    public function testPollingSecondsHasAFloorOfFive(): void
    {
        $provider = $this->createStub(UnreadNotificationsProvider::class);

        $this->assertSame(5, (new Notifications($provider, pollingSeconds: 1))->pollingSeconds());
        $this->assertSame(30, (new Notifications($provider, pollingSeconds: 30))->pollingSeconds());
    }
}
