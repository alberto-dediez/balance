<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use PHPUnit\Framework\TestCase;

final class MaterialityAnnexTopicLineTest extends TestCase
{
    /** @return iterable<string, array{MaterialityCategoryState, bool}> */
    public static function states(): iterable
    {
        foreach (MaterialityCategoryState::cases() as $state) {
            yield $state->value => [$state, in_array($state, [MaterialityCategoryState::ExcludedRecommended, MaterialityCategoryState::ExcludedOverridden], true)];
        }
    }

    /** @dataProvider states */
    public function testOnlyTheExcludedStatesAreExclusions(MaterialityCategoryState $state, bool $excluded): void
    {
        $this->assertSame($excluded, OrganizationalCarbonFootprintReportMaterialityAnnexMother::line($state)->isExcluded());
    }
}
