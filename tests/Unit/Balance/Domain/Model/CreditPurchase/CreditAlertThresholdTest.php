<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use PHPUnit\Framework\TestCase;

class CreditAlertThresholdTest extends TestCase
{
    public function testNoThresholdIsReachedBelowEightyPercent(): void
    {
        $this->assertNull(CreditAlertThreshold::highestReachedBy(0.0, 100.0));
        $this->assertNull(CreditAlertThreshold::highestReachedBy(79.9, 100.0));
    }

    public function testHighestReachedThresholdAtEachBorder(): void
    {
        $this->assertSame(CreditAlertThreshold::Warning, CreditAlertThreshold::highestReachedBy(80.0, 100.0));
        $this->assertSame(CreditAlertThreshold::Warning, CreditAlertThreshold::highestReachedBy(89.9, 100.0));
        $this->assertSame(CreditAlertThreshold::High, CreditAlertThreshold::highestReachedBy(90.0, 100.0));
        $this->assertSame(CreditAlertThreshold::High, CreditAlertThreshold::highestReachedBy(99.9, 100.0));
        $this->assertSame(CreditAlertThreshold::Depleted, CreditAlertThreshold::highestReachedBy(100.0, 100.0));
    }

    public function testDepletedIsReachedWhenAvailableTonnesFallWithinTheTolerance(): void
    {
        $this->assertSame(
            CreditAlertThreshold::Depleted,
            CreditAlertThreshold::highestReachedBy(99.9999995, 100.0),
        );
        $this->assertSame(
            CreditAlertThreshold::High,
            CreditAlertThreshold::highestReachedBy(99.99, 100.0),
        );
    }

    public function testIsReachedBy(): void
    {
        $this->assertTrue(CreditAlertThreshold::Warning->isReachedBy(85.0, 100.0));
        $this->assertFalse(CreditAlertThreshold::High->isReachedBy(85.0, 100.0));
        $this->assertFalse(CreditAlertThreshold::Depleted->isReachedBy(99.9, 100.0));
    }

    public function testAndLowerReturnsTheThresholdAndEveryLowerOne(): void
    {
        $this->assertSame([CreditAlertThreshold::Warning], CreditAlertThreshold::Warning->andLower());
        $this->assertSame(
            [CreditAlertThreshold::Warning, CreditAlertThreshold::High],
            CreditAlertThreshold::High->andLower(),
        );
        $this->assertSame(
            [CreditAlertThreshold::Warning, CreditAlertThreshold::High, CreditAlertThreshold::Depleted],
            CreditAlertThreshold::Depleted->andLower(),
        );
    }
}
