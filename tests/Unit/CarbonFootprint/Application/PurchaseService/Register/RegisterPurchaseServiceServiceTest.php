<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\Register;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Application\PurchaseService\Register\RegisterPurchaseServiceRequest;
use App\CarbonFootprint\Application\PurchaseService\Register\RegisterPurchaseServiceService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRegistered;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterPurchaseServiceServiceTest extends TestCase
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
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterPurchaseServiceRequest(
            name: 'Servicio de sonido',
            category: PurchaseServiceCategory::Technology,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->purchaseServiceId);
        $this->assertEquals(36, strlen((string) $result->purchaseServiceId));
        $this->assertEquals('Servicio de sonido', $result->name);

        $purchaseServicePersisted = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseServicePersisted);
        $this->assertSame(Unit::Euro, $purchaseServicePersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PurchaseServiceRegistered);
        $this->assertEquals('Servicio de sonido', $lastEvent->name());
        $this->assertSame(Unit::Euro, $lastEvent->unit());
        $this->assertTrue($purchaseServicePersisted->id()->equals($lastEvent->purchaseServiceId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterPurchaseServiceRequest(
            name: 'changedName',
            category: PurchaseServiceCategory::Technology,
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $purchaseServicePersisted = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseServicePersisted);
        $this->assertEquals('changedName', $purchaseServicePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PurchaseServiceTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($purchaseServicePersisted->id()->equals($lastEvent->purchaseServiceId()));
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterPurchaseServiceRequest(name: 'test'), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterPurchaseServiceRequest(
            name: 'test',
            category: PurchaseServiceCategory::ProfessionalServices,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PurchaseServiceRepository $repository, Locale $locale): RegisterPurchaseServiceService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterPurchaseServiceService(
            purchaseServiceRepository: $repository,
            authorizer: new PurchaseServiceAuthorizer(),
        );
    }
}
