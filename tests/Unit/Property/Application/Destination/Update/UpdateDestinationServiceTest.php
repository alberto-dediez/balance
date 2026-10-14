<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Destination\Update;

use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Application\Destination\Update\UpdateDestinationRequest;
use App\Property\Application\Destination\Update\UpdateDestinationService;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationUpdated;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateDestinationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $destination = DestinationMother::fromName('MyDestination');

        $repository->add($destination);

        $sut = $this->createSUT($repository);

        $request = new UpdateDestinationRequest(
            destinationId: $destination->id(),
            name: 'Barcelona',
            countryId: new CountryId('6666'),
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $destinationPersisted = $repository->find($result->destinationId);

        $this->assertNotNull($destinationPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DestinationUpdated);
        $this->assertEquals('Barcelona', $lastEvent->name());
        $countryId = $lastEvent->countryId();
        self::assertNotNull($countryId);
        $this->assertTrue($request->countryId->equals($countryId));
        $this->assertTrue($destinationPersisted->id()->equals($lastEvent->destinationId()));
    }

    public function testIsHandle(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $destination = DestinationMother::random();

        $result = $sut->isHandled(new UpdateDestinationRequest(
            destinationId: $destination->id(),
            name: 'Barcelona',
            countryId: CountryId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryDestinationRepository $repository): UpdateDestinationService
    {
        return new UpdateDestinationService($repository, new DestinationAuthorizer());
    }
}
