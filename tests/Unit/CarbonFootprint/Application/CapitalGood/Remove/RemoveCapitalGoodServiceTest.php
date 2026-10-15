<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CapitalGood\Remove;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\Remove\RemoveCapitalGoodRequest;
use App\CarbonFootprint\Application\CapitalGood\Remove\RemoveCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRemoved;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveCapitalGoodServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $capitalGood = CapitalGoodMother::registeredCapitalGood();

        $repository->add($capitalGood);

        $request = new RemoveCapitalGoodRequest(
            capitalGoodId: $capitalGood->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($capitalGood->id()->equals($result->capitalGoodId));

        $capitalGoodPersisted = $repository->find($result->capitalGoodId);

        $this->assertNull($capitalGoodPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CapitalGoodRemoved);
    }

    public function testThrowsWhenNotFound(): void
    {
        $sut = $this->createSUT(CapitalGoodRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemoveCapitalGoodRequest(capitalGoodId: CapitalGoodId::generate()), UserMother::adminUser());
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();
        $capitalGood = CapitalGoodMother::random();
        $repository->add($capitalGood);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveCapitalGoodRequest(capitalGoodId: $capitalGood->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $capitalGood = CapitalGoodMother::random();

        $result = $sut->isHandled(new RemoveCapitalGoodRequest(
            capitalGoodId: $capitalGood->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CapitalGoodRepository $repository): RemoveCapitalGoodService
    {
        return new RemoveCapitalGoodService($repository, new CapitalGoodAuthorizer());
    }
}
