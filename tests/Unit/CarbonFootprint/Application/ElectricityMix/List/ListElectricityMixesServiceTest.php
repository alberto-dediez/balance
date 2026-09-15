<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\List;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\List\ListElectricityMixesRequest;
use App\CarbonFootprint\Application\ElectricityMix\List\ListElectricityMixesService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListElectricityMixesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());

        $request = new ListElectricityMixesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->electricityMixes);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByCountry(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ElectricityMixMother::fromCountry('C-ES'));
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());

        $request = new ListElectricityMixesRequest(countryId: new CountryId('C-ES'));

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->electricityMixes);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListElectricityMixesRequest(countryId: new CountryId('C-FR'));

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(0, $result->electricityMixes);
        $this->assertEquals(0, $result->totalResults);
    }

    private function createSUT(ElectricityMixRepository $repository): ListElectricityMixesService
    {
        return new ListElectricityMixesService($repository, new ElectricityMixAuthorizer());
    }
}
