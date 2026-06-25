<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\Register;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Application\Material\Register\RegisterMaterialRequest;
use App\CarbonFootprint\Application\Material\Register\RegisterMaterialService;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialRegistered;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\Material\MaterialTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMaterialServiceTest extends TestCase
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
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterMaterialRequest(
            name: 'Material 1',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->materialId);
        $this->assertEquals(36, strlen((string) $result->materialId));
        $this->assertEquals('Material 1', $result->name);

        $materialPersisted = $repository->find($result->materialId);

        $this->assertNotNull($materialPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MaterialRegistered);
        $this->assertEquals('Material 1', $lastEvent->name());
        $this->assertTrue($materialPersisted->id()->equals($lastEvent->materialId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterMaterialRequest(
            name: 'changedName',
            category: MaterialCategory::Glass,
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $materialPersisted = $repository->find($result->materialId);

        $this->assertNotNull($materialPersisted);
        $this->assertEquals('changedName', $materialPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MaterialTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($materialPersisted->id()->equals($lastEvent->materialId()));
    }

    public function testIsHandle(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterMaterialRequest(
            name: 'test',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MaterialRepository $repository, Locale $locale): RegisterMaterialService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMaterialService(
            materialRepository: $repository,
            authorizer: new MaterialAuthorizer(),
        );
    }
}
