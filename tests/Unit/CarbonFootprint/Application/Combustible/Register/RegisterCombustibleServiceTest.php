<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible\Register;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\Register\RegisterCombustibleRequest;
use App\CarbonFootprint\Application\Combustible\Register\RegisterCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRegistered;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterCombustibleServiceTest extends TestCase
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
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterCombustibleRequest(
            name: 'Combustible 1',
            unit: Unit::Kilogram,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->combustibleId);
        $this->assertEquals(36, strlen((string) $result->combustibleId));
        $this->assertEquals('Combustible 1', $result->name);

        $combustiblePersisted = $repository->find($result->combustibleId);

        $this->assertNotNull($combustiblePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CombustibleRegistered);
        $this->assertEquals('Combustible 1', $lastEvent->name());
        $this->assertTrue($combustiblePersisted->id()->equals($lastEvent->combustibleId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterCombustibleRequest(
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $combustiblePersisted = $repository->find($result->combustibleId);

        $this->assertNotNull($combustiblePersisted);
        $this->assertEquals('changedName', $combustiblePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CombustibleTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($combustiblePersisted->id()->equals($lastEvent->combustibleId()));
    }

    public function testIsHandle(): void
    {
        $repository = CombustibleRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterCombustibleRequest(
            name: 'test',
            unit: Unit::Kilogram,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CombustibleRepository $repository, Locale $locale): RegisterCombustibleService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterCombustibleService(
            combustibleRepository: $repository,
            authorizer: new CombustibleAuthorizer(),
        );
    }
}
