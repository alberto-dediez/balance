<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkFilter;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TripCalculationLinkRepositoryTest extends KernelTestCase
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

    public function testFindForAccountReturnsTheAccountLinkAndNotThePropertyOnes(): void
    {
        $repository = $this->createSUT();

        $accountId = (string) AccountId::generate();
        $repository->add(TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), $accountId));
        $accountLink = TripCalculationLinkMother::forAccount($accountId);
        $repository->add($accountLink);
        $repository->add(TripCalculationLinkMother::forAccount((string) AccountId::generate()));

        $recovered = $repository->findForAccount(new AccountId($accountId));

        $this->assertNotNull($recovered);
        $this->assertEquals($accountLink->id(), $recovered->id());
        $this->assertNull($recovered->propertyId());
    }

    public function testFindForAccountReturnsNullWhenTheAccountOnlyHasPropertyLinks(): void
    {
        $repository = $this->createSUT();

        $accountId = (string) AccountId::generate();
        $repository->add(TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), $accountId));

        $this->assertNull($repository->findForAccount(new AccountId($accountId)));
    }

    public function testPersistsTheDestinationCountriesAndProperties(): void
    {
        $repository = $this->createSUT();

        $accountId = (string) AccountId::generate();
        $countryId = CountryId::generate();
        $link = TripCalculationLinkMother::forAccountWithDestination($accountId, TripDestinationMode::Countries, countryIds: [$countryId]);
        $repository->add($link);

        $recovered = $repository->findForAccount(new AccountId($accountId));

        $this->assertNotNull($recovered);
        $this->assertSame(TripDestinationMode::Countries, $recovered->destinationMode());
        $this->assertEquals([$countryId], $recovered->destinationCountryIds());

        $propertyId = PropertyId::generate();
        $recovered->updateDestination(TripDestinationMode::SelectedProperties, $recovered->destinationPlaceKind(), [], [$propertyId], null, null, null);
        $repository->add($recovered);

        $recovered = $repository->findForAccount(new AccountId($accountId));

        $this->assertNotNull($recovered);
        $this->assertSame([], $recovered->destinationCountryIds());
        $this->assertEquals([$propertyId], $recovered->destinationPropertyIds());
    }

    public function testSavesAgainTheSameDestinationCountriesAndProperties(): void
    {
        $repository = $this->createSUT();

        $accountId = (string) AccountId::generate();
        $countryId = CountryId::generate();
        $link = TripCalculationLinkMother::forAccountWithDestination($accountId, TripDestinationMode::Countries, countryIds: [$countryId]);
        $repository->add($link);

        $link->updateDestination(TripDestinationMode::Countries, $link->destinationPlaceKind(), [$countryId], [], null, null, null);
        $repository->add($link);

        $propertyId = PropertyId::generate();
        $link->updateDestination(TripDestinationMode::SelectedProperties, $link->destinationPlaceKind(), [], [$propertyId], null, null, null);
        $repository->add($link);
        $link->updateDestination(TripDestinationMode::SelectedProperties, $link->destinationPlaceKind(), [], [$propertyId], null, null, null);
        $repository->add($link);

        $recovered = $repository->findForAccount(new AccountId($accountId));

        $this->assertNotNull($recovered);
        $this->assertSame([], $recovered->destinationCountryIds());
        $this->assertEquals([$propertyId], $recovered->destinationPropertyIds());
    }

    abstract protected function createSUT(): TripCalculationLinkRepository;
}
