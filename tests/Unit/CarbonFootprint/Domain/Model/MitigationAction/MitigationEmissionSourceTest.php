<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use PHPUnit\Framework\TestCase;

class MitigationEmissionSourceTest extends TestCase
{
    public function testCompareOrdersByScopeThenSourceThenItemName(): void
    {
        $emissionSources = [
            MitigationEmissionSourceMother::from(Source::UpstreamTransport, 'transport', 100.0, 'Camión'),
            MitigationEmissionSourceMother::from(Source::CapitalGoods, 'b', 100.0, 'Bomba'),
            MitigationEmissionSourceMother::from(Source::CapitalGoods, 'a', 100.0, 'Ascensor'),
            MitigationEmissionSourceMother::electricity(),
            MitigationEmissionSourceMother::naturalGas(),
        ];

        usort($emissionSources, MitigationEmissionSource::compare(...));

        $this->assertSame(
            ['natural-gas', 'marketer', 'a', 'b', 'transport'],
            array_map(static fn(MitigationEmissionSource $emissionSource): string => $emissionSource->itemId, $emissionSources),
        );
    }
}
