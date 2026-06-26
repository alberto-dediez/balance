<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\List;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\List\ListElectricityMarketersRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\List\ListElectricityMarketersService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListElectricityMarketersServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());
        $repository->add(ElectricityMarketerMother::random());

        $request = new ListElectricityMarketersRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->electricityMarketers);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ElectricityMarketerMother::fromName('name-1'));
        $repository->add(ElectricityMarketerMother::fromName('name-2'));
        $repository->add(ElectricityMarketerMother::fromName('name-2'));
        $repository->add(ElectricityMarketerMother::fromName('name-2'));

        $request = new ListElectricityMarketersRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->electricityMarketers);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListElectricityMarketersRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->electricityMarketers);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(ElectricityMarketerRepository $repository): ListElectricityMarketersService
    {
        return new ListElectricityMarketersService($repository, new ElectricityMarketerAuthorizer());
    }
}
