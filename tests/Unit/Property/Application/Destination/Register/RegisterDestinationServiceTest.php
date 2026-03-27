<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Destination\Register;

use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Application\Destination\Register\RegisterDestinationRequest;
use App\Property\Application\Destination\Register\RegisterDestinationService;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationRegistered;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

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
            countryId: CountryId::generate(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->destinationId);
        $this->assertEquals(36, strlen((string) $result->destinationId));

        $destinationPersisted = $repository->find($result->destinationId);

        $this->assertNotNull($destinationPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DestinationRegistered);
        $this->assertEquals('Spain', $lastEvent->name());
        $this->assertTrue($destinationPersisted->id()->equals($lastEvent->destinationId()));
        $countryId = $lastEvent->countryId();
        self::assertNotNull($countryId);
        $this->assertTrue($request->countryId->equals($countryId));
    }

    public function testIsHandle(): void
    {
        $repository = DestinationRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterDestinationRequest(
            name: 'Destination',
            countryId: CountryId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryDestinationRepository $repository): RegisterDestinationService
    {
        return new RegisterDestinationService($repository, new DestinationAuthorizer());
    }
}
