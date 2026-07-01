<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmissionFactorGroup\Remove;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Application\EmissionFactorGroup\Remove\RemoveEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\Remove\RemoveEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRemoved;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveEmissionFactorGroupServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $emissionFactorGroup = EmissionFactorGroupMother::registeredEmissionFactorGroup();

        $repository->add($emissionFactorGroup);

        $request = new RemoveEmissionFactorGroupRequest(
            emissionFactorGroupId: $emissionFactorGroup->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($emissionFactorGroup->id()->equals($result->emissionFactorGroupId));

        $emissionFactorGroupPersisted = $repository->find($result->emissionFactorGroupId);

        $this->assertNull($emissionFactorGroupPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmissionFactorGroupRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $emissionFactorGroup = EmissionFactorGroupMother::random();

        $result = $sut->isHandled(new RemoveEmissionFactorGroupRequest(
            emissionFactorGroupId: $emissionFactorGroup->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EmissionFactorGroupRepository $repository): RemoveEmissionFactorGroupService
    {
        return new RemoveEmissionFactorGroupService($repository, new EmissionFactorGroupAuthorizer());
    }
}
