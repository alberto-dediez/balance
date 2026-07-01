<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class EmissionFactorTest extends TestCase
{
    public function testGasesAreZeroFallsBackToCo2eqAsCo2(): void
    {
        $emissionFactorGroupId = EmissionFactorGroupMother::default()->id();

        $emissionFactor = new EmissionFactor(
            co2eq: '1.5',
            co2: '0',
            ch4: '0',
            n2o: '0',
            year: 2024,
            emissionFactorGroupId: $emissionFactorGroupId,
        );

        $this->assertSame('1.5', $emissionFactor->co2eq());
        $this->assertSame('1.5', $emissionFactor->co2());
    }

    public function testDoesNotRecomputeCo2eqFromGases(): void
    {
        $emissionFactorGroupId = EmissionFactorGroupMother::default()->id();

        $emissionFactor = new EmissionFactor(
            co2eq: '3',
            co2: '1',
            ch4: '1',
            n2o: '1',
            year: 2024,
            emissionFactorGroupId: $emissionFactorGroupId,
        );

        $this->assertSame('3', $emissionFactor->co2eq());
        $this->assertSame('1', $emissionFactor->co2());
        $this->assertSame('1', $emissionFactor->ch4());
        $this->assertSame('1', $emissionFactor->n2o());
    }

    public function testWithEmissionFactorGroupReplacesGroupWithoutRecalculatingCo2eq(): void
    {
        $originalGroupId = EmissionFactorGroupMother::default()->id();
        $newGroupId = EmissionFactorGroupMother::random()->id();

        $emissionFactor = new EmissionFactor(
            co2eq: '3',
            co2: '1',
            ch4: '1',
            n2o: '1',
            year: 2024,
            emissionFactorGroupId: $originalGroupId,
        );

        $withNewGroup = $emissionFactor->withEmissionFactorGroup($newGroupId);

        $this->assertTrue($originalGroupId->equals($emissionFactor->emissionFactorGroupId()));
        $this->assertTrue($newGroupId->equals($withNewGroup->emissionFactorGroupId()));
        $this->assertSame($emissionFactor->co2eq(), $withNewGroup->co2eq());
        $this->assertSame($emissionFactor->co2(), $withNewGroup->co2());
        $this->assertSame($emissionFactor->ch4(), $withNewGroup->ch4());
        $this->assertSame($emissionFactor->n2o(), $withNewGroup->n2o());
        $this->assertSame($emissionFactor->year(), $withNewGroup->year());
        $this->assertSame($emissionFactor->unit(), $withNewGroup->unit());
    }

    public function testWithUnitAndWithYearPropagateEmissionFactorGroupId(): void
    {
        $emissionFactorGroupId = EmissionFactorGroupMother::default()->id();

        $emissionFactor = new EmissionFactor(
            co2eq: '3',
            co2: '1',
            ch4: '1',
            n2o: '1',
            year: 2024,
            emissionFactorGroupId: $emissionFactorGroupId,
        );

        $withUnit = $emissionFactor->withUnit(Unit::Piece);
        $withYear = $emissionFactor->withYear(2025);

        $this->assertTrue($emissionFactorGroupId->equals($withUnit->emissionFactorGroupId()));
        $this->assertTrue($emissionFactorGroupId->equals($withYear->emissionFactorGroupId()));
    }

    public function testEqualsReturnsFalseWhenEmissionFactorGroupDiffers(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();
        $otherGroupId = EmissionFactorGroupMother::random()->id();

        $emissionFactor = new EmissionFactor(
            co2eq: '3',
            co2: '1',
            ch4: '1',
            n2o: '1',
            year: 2024,
            emissionFactorGroupId: $groupId,
        );

        $sameFactorDifferentGroup = new EmissionFactor(
            co2eq: '3',
            co2: '1',
            ch4: '1',
            n2o: '1',
            year: 2024,
            emissionFactorGroupId: $otherGroupId,
        );

        $sameFactorSameGroup = new EmissionFactor(
            co2eq: '3',
            co2: '1',
            ch4: '1',
            n2o: '1',
            year: 2024,
            emissionFactorGroupId: $groupId,
        );

        $this->assertFalse($emissionFactor->equals($sameFactorDifferentGroup));
        $this->assertTrue($emissionFactor->equals($sameFactorSameGroup));
    }
}
