<?php

namespace App\Tests\Unit\Property\Application\Destination\Register;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Destination\Destination;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRegistered;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Destination\Register\RegisterDestinationRequest;
use App\Property\Application\Destination\Register\RegisterDestinationService;
use App\Property\Application\Destination\Register\RegisterDestinationResponse;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;

class RegisterDestinationServiceTest extends TestCase
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

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new RegisterDestinationRequest(
            name: 'Spain',
            countryId: CountryId::generate()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterDestinationResponse);
        $this->assertNotEmpty((string) $result->destinationId);
        $this->assertEquals(36, strlen((string) $result->destinationId));

        $destinationPersisted = $repository->find($result->destinationId);

        $this->assertNotNull($destinationPersisted);
        $this->assertTrue($destinationPersisted instanceof Destination);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DestinationRegistered);
        $this->assertEquals('Spain', $lastEvent->name());
        $this->assertTrue($destinationPersisted->id()->equals($lastEvent->destinationId()));
        $this->assertTrue($request->countryId->equals($lastEvent->countryId()));
    }

    public function testIsHandle(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterDestinationRequest(
            name: 'Destination',
            countryId: CountryId::generate()
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryDestinationRepository $repository): RegisterDestinationService
    {
        return new RegisterDestinationService($repository, new DestinationAuthorizer());
    }
}