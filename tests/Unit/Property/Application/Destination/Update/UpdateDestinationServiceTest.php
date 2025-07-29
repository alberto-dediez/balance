<?php

namespace App\Tests\Unit\Property\Application\Destination\Update;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Destination\Destination;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Property\Domain\Model\Destination\DestinationUpdated;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Property\Application\Destination\Update\UpdateDestinationRequest;
use App\Property\Application\Destination\Update\UpdateDestinationService;
use App\Property\Application\Destination\Update\UpdateDestinationResponse;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;

class UpdateDestinationServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = DestinationRepositoryMother::inMemory();

        $destination = DestinationMother::fromName('MyDestination');

        $repository->add($destination);

        $sut = $this->createSUT($repository);

        $request = new UpdateDestinationRequest(
            destinationId: $destination->id(),
            name: 'Barcelona',
            countryId: new CountryId('6666')
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateDestinationResponse);

        $destinationPersisted = $repository->find($result->destinationId);

        $this->assertNotNull($destinationPersisted);
        $this->assertTrue($destinationPersisted instanceof Destination);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DestinationUpdated);
        $this->assertEquals('Barcelona', $lastEvent->name());
        $this->assertTrue($request->countryId->equals($lastEvent->countryId()));
        $this->assertTrue($destinationPersisted->id()->equals($lastEvent->destinationId()));
    }

    private function createSUT($repository)
    {
        return new UpdateDestinationService($repository, new DestinationAuthorizer());
    }
}