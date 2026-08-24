<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkFilter;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

abstract class TripCalculationLinkRepositoryTest extends TestCase
{
    public function testAddTripCalculationLinkToRepository(): void
    {
        $repository = $this->createSUT();

        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $this->assertNull($repository->find($tripCalculationLink->id()));

        $repository->add($tripCalculationLink);

        $recovered = $repository->find($tripCalculationLink->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($tripCalculationLink->id(), $recovered->id());
    }

    public function testFindByPropertyId(): void
    {
        $repository = $this->createSUT();

        $propertyId = (string) PropertyId::generate();
        $tripCalculationLink = TripCalculationLinkMother::fromPropertyId($propertyId);

        $repository->add($tripCalculationLink);

        $recovered = $repository->findByPropertyId(new PropertyId($propertyId));

        $this->assertNotNull($recovered);
        $this->assertEquals($tripCalculationLink->id(), $recovered->id());
    }

    public function testFindByPropertyIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByPropertyId(PropertyId::generate()));
    }

    public function testFindByFilterByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = (string) AccountId::generate();
        $repository->add(TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), $accountId));
        $repository->add(TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), $accountId));
        $repository->add(TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), (string) AccountId::generate()));

        $filter = new TripCalculationLinkFilter(accountId: new AccountId($accountId), limit: 0);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter(new TripCalculationLinkFilter(limit: 0)));
    }

    public function testFindByToken(): void
    {
        $repository = $this->createSUT();

        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $repository->add($tripCalculationLink);

        $recovered = $repository->findByToken($tripCalculationLink->token());

        $this->assertNotNull($recovered);
        $this->assertEquals($tripCalculationLink->id(), $recovered->id());
    }

    public function testFindByTokenReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByToken('invalid-token'));
    }

    abstract protected function createSUT(): TripCalculationLinkRepository;
}
