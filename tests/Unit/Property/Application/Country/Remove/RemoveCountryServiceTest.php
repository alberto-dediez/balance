<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Country\Remove;

use App\Property\Application\Country\CountryAuthorizer;
use App\Property\Application\Country\Remove\RemoveCountryRequest;
use App\Property\Application\Country\Remove\RemoveCountryResponse;
use App\Property\Application\Country\Remove\RemoveCountryService;
use App\Property\Domain\Model\Country\CountryRemoved;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveCountryServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = CountryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $country = CountryMother::random();

        $repository->add($country);

        $request = new RemoveCountryRequest(
            countryId: $country->id(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveCountryResponse);
        $this->assertTrue($country->id()->equals($result->countryId));

        $countryPersisted = $repository->find($result->countryId);

        $this->assertNull($countryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CountryRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = CountryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $country = CountryMother::random();

        $result = $sut->isHandled(new RemoveCountryRequest(
            countryId: $country->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryCountryRepository $repository): RemoveCountryService
    {
        return new RemoveCountryService($repository, new CountryAuthorizer());
    }
}
