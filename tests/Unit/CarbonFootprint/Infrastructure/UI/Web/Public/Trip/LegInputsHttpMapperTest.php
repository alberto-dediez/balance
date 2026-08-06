<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\Public\Trip;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\Trip\LegInputsHttpMapper;
use PHPUnit\Framework\TestCase;

class LegInputsHttpMapperTest extends TestCase
{
    public function testMapsAFullyFilledLegToSessionData(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => '3',
            'destinationPlaceReference' => 'place-id',
            'manualDistanceKm' => '120.5',
        ]]]);

        $this->assertSame([[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => 3,
            'destinationPlaceReference' => 'place-id',
            'manualDistanceKm' => 120.5,
        ]], $data);
    }

    public function testAcceptsADecimalDistanceWrittenWithAComma(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [[
            'mobilityId' => 'mobility-id',
            'manualDistanceKm' => '120,5',
        ]]]);

        $this->assertSame(120.5, $data[0]['manualDistanceKm']);
    }

    public function testEmptyOptionalFieldsBecomeNullInSessionData(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => '',
            'destinationPlaceReference' => '',
            'manualDistanceKm' => '',
        ]]]);

        $this->assertNull($data[0]['vehicleOccupants']);
        $this->assertNull($data[0]['destinationPlaceReference']);
        $this->assertNull($data[0]['manualDistanceKm']);
    }

    public function testMissingOptionalFieldsBecomeNullInSessionData(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [['mobilityId' => 'mobility-id']]]);

        $this->assertSame([[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => null,
            'destinationPlaceReference' => null,
            'manualDistanceKm' => null,
        ]], $data);
    }

    public function testAZeroDistanceIsDiscardedAsEmpty(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => '0',
            'manualDistanceKm' => '0',
        ]]]);

        $this->assertNull($data[0]['vehicleOccupants']);
        $this->assertNull($data[0]['manualDistanceKm']);
    }

    public function testSkipsLegsWithoutMobility(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [
            ['mobilityId' => '', 'manualDistanceKm' => '10'],
            ['manualDistanceKm' => '20'],
            ['mobilityId' => 'mobility-id'],
        ]]);

        $this->assertCount(1, $data);
        $this->assertSame('mobility-id', $data[0]['mobilityId']);
    }

    public function testSkipsLegsThatAreNotAnArray(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => ['not-a-leg', ['mobilityId' => 'mobility-id']]]);

        $this->assertCount(1, $data);
        $this->assertSame('mobility-id', $data[0]['mobilityId']);
    }

    public function testABlockWithoutLegsProducesNoSessionData(): void
    {
        $this->assertSame([], LegInputsHttpMapper::toSessionData([]));
        $this->assertSame([], LegInputsHttpMapper::toSessionData(['legs' => []]));
        $this->assertSame([], LegInputsHttpMapper::toSessionData(['legs' => 'not-an-array']));
    }

    public function testSessionDataKeepsTheOrderOfTheLegs(): void
    {
        $data = LegInputsHttpMapper::toSessionData(['legs' => [
            ['mobilityId' => 'first'],
            ['mobilityId' => ''],
            ['mobilityId' => 'second'],
        ]]);

        $this->assertSame(['first', 'second'], array_column($data, 'mobilityId'));
    }

    public function testMapsSessionDataToLegInputs(): void
    {
        $legInputs = LegInputsHttpMapper::toLegInputs([[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => 3,
            'destinationPlaceReference' => 'place-id',
            'manualDistanceKm' => 120.5,
        ]]);

        $this->assertCount(1, $legInputs);
        $this->assertSame('mobility-id', $legInputs[0]->mobilityId);
        $this->assertSame(3, $legInputs[0]->vehicleOccupants);
        $this->assertSame('place-id', $legInputs[0]->destinationPlaceReference);
        $this->assertSame(120.5, $legInputs[0]->manualDistanceKm);
    }

    public function testLegInputsKeepTheNullOptionalFields(): void
    {
        $legInputs = LegInputsHttpMapper::toLegInputs([[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => null,
            'destinationPlaceReference' => null,
            'manualDistanceKm' => null,
        ]]);

        $this->assertNull($legInputs[0]->vehicleOccupants);
        $this->assertNull($legInputs[0]->destinationPlaceReference);
        $this->assertNull($legInputs[0]->manualDistanceKm);
    }

    public function testLegInputsAreBuiltFromSessionDataWithMissingOptionalKeys(): void
    {
        $legInputs = LegInputsHttpMapper::toLegInputs([['mobilityId' => 'mobility-id']]);

        $this->assertCount(1, $legInputs);
        $this->assertNull($legInputs[0]->vehicleOccupants);
        $this->assertNull($legInputs[0]->manualDistanceKm);
    }

    public function testSkipsLegInputsWithoutMobility(): void
    {
        $legInputs = LegInputsHttpMapper::toLegInputs([
            ['mobilityId' => ''],
            ['vehicleOccupants' => 2],
            'not-a-leg',
            ['mobilityId' => 'mobility-id'],
        ]);

        $this->assertCount(1, $legInputs);
        $this->assertSame('mobility-id', $legInputs[0]->mobilityId);
    }

    public function testTheRoundTripThroughSessionDataPreservesTheLegs(): void
    {
        $sessionData = LegInputsHttpMapper::toSessionData(['legs' => [[
            'mobilityId' => 'mobility-id',
            'vehicleOccupants' => '4',
            'destinationPlaceReference' => 'place-id',
            'manualDistanceKm' => '1234,5',
        ]]]);

        $legInputs = LegInputsHttpMapper::toLegInputs($sessionData);

        $this->assertCount(1, $legInputs);
        $this->assertSame('mobility-id', $legInputs[0]->mobilityId);
        $this->assertSame(4, $legInputs[0]->vehicleOccupants);
        $this->assertSame('place-id', $legInputs[0]->destinationPlaceReference);
        $this->assertSame(1234.5, $legInputs[0]->manualDistanceKm);
    }
}
