<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySector\Remove;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Application\MaterialitySector\Remove\RemoveMaterialitySectorRequest;
use App\Materiality\Application\MaterialitySector\Remove\RemoveMaterialitySectorService;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRemoved;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMaterialitySectorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();
        $materialitySector = MaterialitySectorMother::random();
        $repository->add($materialitySector);

        $result = $this->createSUT($repository)(
            new RemoveMaterialitySectorRequest($materialitySector->id()),
            UserMother::adminUser(),
        );

        $this->assertTrue($materialitySector->id()->equals($result->materialitySectorId));
        $this->assertNull($repository->find($materialitySector->id()));
        $this->assertCount(1, $repository->findByFilter(new MaterialitySectorFilter(includeRemoved: true)));
        $this->assertTrue($materialitySector->isRemoved());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(MaterialitySectorRemoved::class, $lastEvent);
        $this->assertTrue($materialitySector->id()->equals($lastEvent->materialitySectorId()));
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialitySectorNotFoundException::class);

        $this->createSUT(MaterialitySectorRepositoryMother::inMemory())(
            new RemoveMaterialitySectorRequest(MaterialitySectorId::generate()),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = MaterialitySectorRepositoryMother::inMemory();
        $materialitySector = MaterialitySectorMother::random();
        $repository->add($materialitySector);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new RemoveMaterialitySectorRequest($materialitySector->id()),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialitySectorRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RemoveMaterialitySectorRequest(MaterialitySectorId::generate())));
    }

    private function createSUT(MaterialitySectorRepository $repository): RemoveMaterialitySectorService
    {
        return new RemoveMaterialitySectorService(
            materialitySectorRepository: $repository,
            authorizer: new MaterialitySectorAuthorizer(),
        );
    }
}
