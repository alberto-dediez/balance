<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmissionFactorGroup\SetDefault;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Application\EmissionFactorGroup\SetDefault\SetDefaultEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\SetDefault\SetDefaultEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupSetAsDefault;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupUnsetAsDefault;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SetDefaultEmissionFactorGroupServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testSetAsDefaultWhenNoPreviousDefaultExists(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::random();
        $repository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new SetDefaultEmissionFactorGroupRequest(emissionFactorGroupId: $emissionFactorGroup->id()),
            UserMother::adminUser(),
        );

        $this->assertTrue($emissionFactorGroup->id()->equals($result->emissionFactorGroupId));
        $this->assertEquals($emissionFactorGroup->name(), $result->name);

        $persisted = $repository->find($emissionFactorGroup->id());

        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isDefault());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmissionFactorGroupSetAsDefault);
        $this->assertTrue($emissionFactorGroup->id()->equals($lastEvent->emissionFactorGroupId()));
    }

    public function testSettingNewDefaultUnsetsThePreviousOne(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $previousDefault = EmissionFactorGroupMother::random();
        $previousDefault->setAsDefault();
        $repository->add($previousDefault);

        $newDefault = EmissionFactorGroupMother::random();
        $repository->add($newDefault);

        $sut = $this->createSUT($repository);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut(
            new SetDefaultEmissionFactorGroupRequest(emissionFactorGroupId: $newDefault->id()),
            UserMother::adminUser(),
        );

        $persistedPreviousDefault = $repository->find($previousDefault->id());
        $persistedNewDefault = $repository->find($newDefault->id());

        $this->assertNotNull($persistedPreviousDefault);
        $this->assertNotNull($persistedNewDefault);
        $this->assertFalse($persistedPreviousDefault->isDefault());
        $this->assertTrue($persistedNewDefault->isDefault());

        $events = $this->spySubscriber->events();

        $this->assertCount(2, $events);
        $this->assertTrue($events[0] instanceof EmissionFactorGroupUnsetAsDefault);
        $this->assertTrue($previousDefault->id()->equals($events[0]->emissionFactorGroupId()));
        $this->assertTrue($events[1] instanceof EmissionFactorGroupSetAsDefault);
        $this->assertTrue($newDefault->id()->equals($events[1]->emissionFactorGroupId()));
    }

    public function testSettingAlreadyDefaultGroupIsANoop(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::random();
        $emissionFactorGroup->setAsDefault();
        $repository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $sut(
            new SetDefaultEmissionFactorGroupRequest(emissionFactorGroupId: $emissionFactorGroup->id()),
            UserMother::adminUser(),
        );

        $this->assertEmpty($this->spySubscriber->events());

        $persisted = $repository->find($emissionFactorGroup->id());

        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isDefault());
    }

    public function testThrowsWhenEmissionFactorGroupNotFound(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new SetDefaultEmissionFactorGroupRequest(emissionFactorGroupId: EmissionFactorGroupId::generate()),
            UserMother::adminUser(),
        );
    }

    public function testDeniesUnauthorizedUser(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::random();
        $repository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new SetDefaultEmissionFactorGroupRequest(emissionFactorGroupId: $emissionFactorGroup->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    public function testIsHandled(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new SetDefaultEmissionFactorGroupRequest(emissionFactorGroupId: EmissionFactorGroupId::generate()));

        $this->assertTrue($result);
    }

    private function createSUT(EmissionFactorGroupRepository $repository): SetDefaultEmissionFactorGroupService
    {
        return new SetDefaultEmissionFactorGroupService(
            emissionFactorGroupRepository: $repository,
            authorizer: new EmissionFactorGroupAuthorizer(),
        );
    }
}
