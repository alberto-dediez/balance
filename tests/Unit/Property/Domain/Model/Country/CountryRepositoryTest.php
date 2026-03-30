<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Country;

use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CountryRepositoryTest extends KernelTestCase
{
    public function testAddCountryToRepository(): void
    {
        $repository = $this->createSUT();

        $country = CountryMother::random();

        $this->assertNull($repository->find($country->id()));

        $repository->add($country);

        $recoverCountry = $repository->find($country->id());

        $this->assertNotNull($recoverCountry);
        $this->assertEquals($country->id(), $recoverCountry->id());
    }

    public function testRemoveCountryToRepository(): void
    {
        $repository = $this->createSUT();

        $country = CountryMother::random();

        $repository->add($country);

        $recoverCountry = $repository->find($country->id());

        $this->assertNotNull($recoverCountry);
        $this->assertEquals($country->id(), $recoverCountry->id());

        $repository->remove($country);

        $this->assertNull($repository->find($country->id()));
    }

    public function testFindByName(): void
    {
        $repository = $this->createSUT();

        $country = CountryMother::random();

        $this->assertNull($repository->findByName($country->name()));

        $repository->add($country);

        $recoverCountry = $repository->findByName($country->name());

        $this->assertNotNull($recoverCountry);
        $this->assertEquals($country->id(), $recoverCountry->id());
    }

    public function testFindByIso(): void
    {
        $repository = $this->createSUT();

        $country = CountryMother::random();

        $iso = $country->iso();
        assert(null !== $iso);

        $this->assertNull($repository->findByIso($iso));

        $repository->add($country);

        $recoverCountry = $repository->findByIso($iso);

        $this->assertNotNull($recoverCountry);
        $this->assertTrue($country->id()->equals($recoverCountry->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::fromName('Spain'));

        $filter = new CountryFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CountryFilter(
            name: 'Spain',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::fromName('Spain'));

        $filter = new CountryFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CountryFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $country = CountryMother::random();

        $repository->add($country);

        $recoverCountry = $repository->find($country->id());

        $this->assertNotNull($recoverCountry);

        $recoverCountry->remove();

        $repository->add($country);

        $this->assertNull($repository->find($country->id()));
    }

    abstract protected function createSUT(): CountryRepository;
}
