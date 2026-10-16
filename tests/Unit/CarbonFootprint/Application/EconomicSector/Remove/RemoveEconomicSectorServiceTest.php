<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\Remove;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\Remove\RemoveEconomicSectorRequest;
use App\CarbonFootprint\Application\EconomicSector\Remove\RemoveEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRemoved;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveEconomicSectorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $economicSector = EconomicSectorMother::registeredEconomicSector();

        $repository->add($economicSector);

        $request = new RemoveEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($economicSector->id()->equals($result->economicSectorId));

        $economicSectorPersisted = $repository->find($result->economicSectorId);

        $this->assertNull($economicSectorPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EconomicSectorRemoved);
    }

    public function testThrowsWhenNotFound(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemoveEconomicSectorRequest(economicSectorId: EconomicSectorId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $economicSector = EconomicSectorMother::random();
        $repository->add($economicSector);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveEconomicSectorRequest(economicSectorId: $economicSector->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $economicSector = EconomicSectorMother::random();

        $result = $sut->isHandled(new RemoveEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EconomicSectorRepository $repository): RemoveEconomicSectorService
    {
        return new RemoveEconomicSectorService($repository, new EconomicSectorAuthorizer());
    }
}
