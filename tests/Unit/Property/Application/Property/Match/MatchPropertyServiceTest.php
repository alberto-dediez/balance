<?php

namespace App\Tests\Unit\Property\Application\Property\Match;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyMatched;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Property\Match\MatchPropertyRequest;
use App\Property\Application\Property\Match\MatchPropertyService;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Property\Application\Property\Match\MatchPropertyResponse;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class MatchPropertyServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testFindByName()
    {
        $destination = DestinationMother::random();

        $repository = PropertyRepositoryMother::inMemory();

        $infoA = new PropertyInfo(
            destinationId: $destination->id(),
            countryId: $destination->countryId()
        );
        $hotelA = PropertyMother::fromNameAndInfo('Hotel Cordoba', $infoA);
        $repository->add($hotelA);

        $infoB = new PropertyInfo(
            destinationId: $destination->id(),
            countryId: $destination->countryId()
        );
        $hotelB = PropertyMother::fromNameAndInfo('Abaco & Spa', $infoB);
        $repository->add($hotelB);

        $hotelC = PropertyMother::fromName('Hotel Abaco & Spa');
        $repository->add($hotelC);

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new MatchPropertyRequest(
            name: 'Hotel Abaco & Spa',
            info: new PropertyInfo(
                destinationId: $destination->id(),
                countryId: $destination->countryId()
            ),
            externalId: new ExternalId(),
            source: ExternalSource::Hotelbeds
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof MatchPropertyResponse);
        $this->assertNotEmpty((string) $result->propertyId);
        $this->assertTrue($result->propertyId->equals($hotelB->id()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyMatched);
        $this->assertEquals('Hotel Abaco & Spa', $lastEvent->name());
    }

    public function testFindByExternalId()
    {
        $destination = DestinationMother::random();

        $repository = PropertyRepositoryMother::inMemory();

        $externalIdA = new ExternalId(
            hotelbeds: '123'
        );
        $hotelA = PropertyMother::fromNameAndExternalId('Hotel Cordoba', $externalIdA);
        $repository->add($hotelA);

        $externalIdB = new ExternalId(
            hotelbeds: '666',
            restel: '123'
        );
        $hotelB = PropertyMother::fromNameAndExternalId('Unnamed', $externalIdB);
        $repository->add($hotelB);

        $hotelC = PropertyMother::fromName('Hotel Abaco & Spa');
        $repository->add($hotelC);

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new MatchPropertyRequest(
            name: 'Hotel Abaco & Spa',
            info: new PropertyInfo(
                destinationId: $destination->id(),
                countryId: $destination->countryId()
            ),
            externalId: new ExternalId(
                hotelbeds: '666'
            ),
            source: ExternalSource::Hotelbeds
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof MatchPropertyResponse);
        $this->assertNotEmpty((string) $result->propertyId);
        $this->assertTrue($result->propertyId->equals($hotelB->id()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyMatched);
        $this->assertEquals('Hotel Abaco & Spa', $lastEvent->name());
    }

    private function createSUT($repository)
    {
        return new MatchPropertyService($repository, new PropertyAuthorizer());
    }
}