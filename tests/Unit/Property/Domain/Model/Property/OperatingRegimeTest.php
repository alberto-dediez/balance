<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\OperatingRegime;
use PHPUnit\Framework\TestCase;

class OperatingRegimeTest extends TestCase
{
    /**
     * @return iterable<string, array{OperatingRegime, bool}>
     */
    public static function regimes(): iterable
    {
        yield 'owned' => [OperatingRegime::Owned, true];
        yield 'leased' => [OperatingRegime::Leased, true];
        yield 'managed' => [OperatingRegime::Managed, true];
        yield 'franchised' => [OperatingRegime::Franchised, false];
        yield 'leased out' => [OperatingRegime::LeasedOut, false];
    }

    /** @dataProvider regimes */
    public function testIsConsolidated(OperatingRegime $regime, bool $expected): void
    {
        $this->assertSame($expected, $regime->isConsolidated());
    }
}
