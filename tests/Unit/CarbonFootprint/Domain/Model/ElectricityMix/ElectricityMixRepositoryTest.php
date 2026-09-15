<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ElectricityMixRepositoryTest extends KernelTestCase
{
    public function testAddElectricityMixToRepository(): void
    {
        $repository = $this->createSUT();

        $electricityMix = ElectricityMixMother::registeredElectricityMix();

        $this->assertNull($repository->find($electricityMix->id()));

        $repository->add($electricityMix);

        $recoveredElectricityMix = $repository->find($electricityMix->id());

        $this->assertNotNull($recoveredElectricityMix);
        $this->assertEquals($electricityMix->id(), $recoveredElectricityMix->id());
    }

    public function testRemoveElectricityMixToRepository(): void
    {
        $repository = $this->createSUT();

        $electricityMix = ElectricityMixMother::registeredElectricityMix();

        $repository->add($electricityMix);

        $recoveredElectricityMix = $repository->find($electricityMix->id());

        $this->assertNotNull($recoveredElectricityMix);
        $this->assertEquals($electricityMix->id(), $recoveredElectricityMix->id());

        $repository->remove($electricityMix);

        $this->assertNull($repository->find($electricityMix->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::fromCountry('C-ES'));

        $filter = new ElectricityMixFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ElectricityMixFilter(
            countryId: new CountryId('C-ES'),
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::random());

        $filter = new ElectricityMixFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $electricityMix = ElectricityMixMother::registeredElectricityMix();

        $repository->add($electricityMix);

        $recoveredElectricityMix = $repository->find($electricityMix->id());

        $this->assertNotNull($recoveredElectricityMix);

        $recoveredElectricityMix->remove();

        $repository->add($electricityMix);

        $this->assertNull($repository->find($electricityMix->id()));
    }

    public function testFindByCountryId(): void
    {
        $repository = $this->createSUT();

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());

        $this->assertNull($repository->findByCountryId(new CountryId('C-ES')));

        $repository->add($electricityMix);

        $recoveredElectricityMix = $repository->findByCountryId(new CountryId('C-ES'));

        $this->assertNotNull($recoveredElectricityMix);
        $this->assertEquals($electricityMix->id(), $recoveredElectricityMix->id());

        $this->assertNull($repository->findByCountryId(new CountryId('C-FR')));
    }

    public function testFindByCountryIdDoesNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());

        $repository->add($electricityMix);

        $electricityMix->remove();

        $repository->add($electricityMix);

        $this->assertNull($repository->findByCountryId(new CountryId('C-ES')));
    }

    public function testFindByFilterFiltersByCountry(): void
    {
        $repository = $this->createSUT();

        $repository->add(ElectricityMixMother::random());
        $repository->add(ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate()));

        $result = $repository->findByFilter(new ElectricityMixFilter(countryId: new CountryId('C-ES')));

        $this->assertCount(1, $result);
    }

    abstract protected function createSUT(): ElectricityMixRepository;
}
