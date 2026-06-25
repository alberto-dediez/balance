<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem\Register;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Application\MerchandisingItem\Register\RegisterMerchandisingItemRequest;
use App\CarbonFootprint\Application\MerchandisingItem\Register\RegisterMerchandisingItemService;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRegistered;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMerchandisingItemServiceTest extends TestCase
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
        $repository = MerchandisingItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterMerchandisingItemRequest(
            name: 'Merchandising Item 1',
            category: MerchandisingItemCategory::Merchandising,
            unit: Unit::Kilogram,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->merchandisingItemId);
        $this->assertEquals(36, strlen((string) $result->merchandisingItemId));
        $this->assertEquals('Merchandising Item 1', $result->name);

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNotNull($merchandisingItemPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MerchandisingItemRegistered);
        $this->assertEquals('Merchandising Item 1', $lastEvent->name());
        $this->assertTrue($merchandisingItemPersisted->id()->equals($lastEvent->merchandisingItemId()));
    }

    public function testRegisterWithUnitWeight(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterMerchandisingItemRequest(
            name: 'T-Shirt',
            category: MerchandisingItemCategory::Clothing,
            unit: Unit::Piece,
            unitWeight: '0.25',
        );

        $result = $sut($request, UserMother::adminUser());

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNotNull($merchandisingItemPersisted);
        $this->assertSame('0.25', $merchandisingItemPersisted->unitWeight());
        $this->assertSame(Unit::Piece, $merchandisingItemPersisted->unit());
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterMerchandisingItemRequest(
            name: 'changedName',
            category: MerchandisingItemCategory::Sport,
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNotNull($merchandisingItemPersisted);
        $this->assertEquals('changedName', $merchandisingItemPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MerchandisingItemTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($merchandisingItemPersisted->id()->equals($lastEvent->merchandisingItemId()));
    }

    public function testIsHandle(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterMerchandisingItemRequest(
            name: 'test',
            category: MerchandisingItemCategory::Merchandising,
            unit: Unit::Kilogram,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MerchandisingItemRepository $repository, Locale $locale): RegisterMerchandisingItemService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMerchandisingItemService(
            merchandisingItemRepository: $repository,
            authorizer: new MerchandisingItemAuthorizer(),
        );
    }
}
