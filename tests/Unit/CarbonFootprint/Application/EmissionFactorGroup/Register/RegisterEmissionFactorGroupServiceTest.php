<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmissionFactorGroup\Register;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Application\EmissionFactorGroup\Register\RegisterEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\Register\RegisterEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRegistered;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupTranslated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterEmissionFactorGroupServiceTest extends TestCase
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
        $repository = EmissionFactorGroupRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterEmissionFactorGroupRequest(
            name: 'AR5',
            gwpCo2: 1.0,
            gwpCh4: 28.0,
            gwpN2O: 265.0,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->emissionFactorGroupId);
        $this->assertEquals(36, strlen((string) $result->emissionFactorGroupId));
        $this->assertEquals('AR5', $result->name);

        $emissionFactorGroupPersisted = $repository->find($result->emissionFactorGroupId);

        $this->assertNotNull($emissionFactorGroupPersisted);
        $this->assertSame(28.0, $emissionFactorGroupPersisted->gwp()->gwpCh4());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmissionFactorGroupRegistered);
        $this->assertEquals('AR5', $lastEvent->name());
        $this->assertTrue($emissionFactorGroupPersisted->id()->equals($lastEvent->emissionFactorGroupId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterEmissionFactorGroupRequest(
            name: 'changedName',
        );

        $result = $sut($request, UserMother::adminUser());

        $emissionFactorGroupPersisted = $repository->find($result->emissionFactorGroupId);

        $this->assertNotNull($emissionFactorGroupPersisted);
        $this->assertEquals('changedName', $emissionFactorGroupPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmissionFactorGroupTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($emissionFactorGroupPersisted->id()->equals($lastEvent->emissionFactorGroupId()));
    }

    public function testIsHandle(): void
    {
        $repository = EmissionFactorGroupRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterEmissionFactorGroupRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EmissionFactorGroupRepository $repository, Locale $locale): RegisterEmissionFactorGroupService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterEmissionFactorGroupService(
            emissionFactorGroupRepository: $repository,
            authorizer: new EmissionFactorGroupAuthorizer(),
        );
    }
}
