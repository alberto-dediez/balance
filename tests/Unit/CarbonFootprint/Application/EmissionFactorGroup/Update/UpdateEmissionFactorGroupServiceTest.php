<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmissionFactorGroup\Update;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Application\EmissionFactorGroup\Update\UpdateEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\Update\UpdateEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupTranslated;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupUpdated;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateEmissionFactorGroupServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::random();

        $repository->add($emissionFactorGroup);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateEmissionFactorGroupRequest(
            emissionFactorGroupId: $emissionFactorGroup->id(),
            code: 'changedCode',
            name: 'changedName',
            gwpCo2: 1.0,
            gwpCh4: 30.0,
            gwpN2O: 280.0,
        );

        $result = $sut($request, $user);

        $emissionFactorGroupPersisted = $repository->find($result->emissionFactorGroupId);

        $this->assertNotNull($emissionFactorGroupPersisted);
        $this->assertSame('changedCode', $emissionFactorGroupPersisted->code());
        $this->assertSame(30.0, $emissionFactorGroupPersisted->gwp()->gwpCh4());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmissionFactorGroupUpdated);
        $this->assertTrue($emissionFactorGroupPersisted->id()->equals($lastEvent->emissionFactorGroupId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory(Locale::Spanish);

        $emissionFactorGroup = EmissionFactorGroupMother::random();

        $repository->add($emissionFactorGroup);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateEmissionFactorGroupRequest(
            emissionFactorGroupId: $emissionFactorGroup->id(),
            code: 'changedCode',
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $emissionFactorGroupPersisted = $repository->find($result->emissionFactorGroupId);

        $this->assertNotNull($emissionFactorGroupPersisted);
        $this->assertEquals('changedName', $emissionFactorGroupPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmissionFactorGroupTranslated);
        $this->assertTrue($emissionFactorGroupPersisted->id()->equals($lastEvent->emissionFactorGroupId()));
    }

    private function createSUT(EmissionFactorGroupRepository $repository, Locale $locale = Locale::Spanish): UpdateEmissionFactorGroupService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateEmissionFactorGroupService(
            emissionFactorGroupRepository: $repository,
            authorizer: new EmissionFactorGroupAuthorizer(),
        );
    }
}
