<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Shared\Infrastructure\ErrorReport\CacheErrorReportThrottle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheErrorReportThrottleTest extends TestCase
{
    public function testItOnlyNotifiesTheFirstTimeTheSameErrorHappens(): void
    {
        $sut = new CacheErrorReportThrottle(new ArrayAdapter(), 60);

        $this->assertTrue($sut->shouldNotify('a-fingerprint'));
        $this->assertFalse($sut->shouldNotify('a-fingerprint'));
        $this->assertTrue($sut->shouldNotify('another-fingerprint'));
    }

    public function testItAlwaysNotifiesWhenTheThrottleIsDisabled(): void
    {
        $sut = new CacheErrorReportThrottle(new ArrayAdapter(), 0);

        $this->assertTrue($sut->shouldNotify('a-fingerprint'));
        $this->assertTrue($sut->shouldNotify('a-fingerprint'));
    }
}
