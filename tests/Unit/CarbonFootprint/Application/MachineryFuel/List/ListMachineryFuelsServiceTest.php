<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\List;

use App\CarbonFootprint\Application\MachineryFuel\List\ListMachineryFuelsRequest;
use App\CarbonFootprint\Application\MachineryFuel\List\ListMachineryFuelsService;
use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMachineryFuelsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());

        $request = new ListMachineryFuelsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->machineryFuels);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MachineryFuelMother::fromName('name-1'));
        $repository->add(MachineryFuelMother::fromName('name-2'));
        $repository->add(MachineryFuelMother::fromName('name-2'));
        $repository->add(MachineryFuelMother::fromName('name-2'));

        $request = new ListMachineryFuelsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->machineryFuels);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListMachineryFuelsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->machineryFuels);
        $this->assertEquals(3, $result->totalResults);
    }

    public function testFilterByMachineryType(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MachineryFuelMother::from(
            name: 'Tractor',
            id: (string) MachineryFuelId::generate(),
            machineryType: MachineryType::Agricultural,
        ));
        $repository->add(MachineryFuelMother::from(
            name: 'Motosierra',
            id: (string) MachineryFuelId::generate(),
            machineryType: MachineryType::Forestry,
        ));
        $repository->add(MachineryFuelMother::from(
            name: 'Generador',
            id: (string) MachineryFuelId::generate(),
            machineryType: MachineryType::CommercialInstitutionalIndustrial,
        ));

        $request = new ListMachineryFuelsRequest(machineryType: MachineryType::Forestry);

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->machineryFuels);
        $this->assertEquals(1, $result->totalResults);
    }

    private function createSUT(MachineryFuelRepository $repository): ListMachineryFuelsService
    {
        return new ListMachineryFuelsService($repository, new MachineryFuelAuthorizer());
    }
}
