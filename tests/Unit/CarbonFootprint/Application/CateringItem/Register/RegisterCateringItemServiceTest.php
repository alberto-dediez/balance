<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem\Register;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\Register\RegisterCateringItemRequest;
use App\CarbonFootprint\Application\CateringItem\Register\RegisterCateringItemService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRegistered;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterCateringItemServiceTest extends TestCase
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
        $repository = CateringItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterCateringItemRequest(
            name: 'Catering Item 1',
            category: CateringItemCategory::Breakfast,
            unit: Unit::Kilogram,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->cateringItemId);
        $this->assertEquals(36, strlen((string) $result->cateringItemId));
        $this->assertEquals('Catering Item 1', $result->name);

        $cateringItemPersisted = $repository->find($result->cateringItemId);

        $this->assertNotNull($cateringItemPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CateringItemRegistered);
        $this->assertEquals('Catering Item 1', $lastEvent->name());
        $this->assertTrue($cateringItemPersisted->id()->equals($lastEvent->cateringItemId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = CateringItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterCateringItemRequest(
            name: 'changedName',
            category: CateringItemCategory::Snack,
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $cateringItemPersisted = $repository->find($result->cateringItemId);

        $this->assertNotNull($cateringItemPersisted);
        $this->assertEquals('changedName', $cateringItemPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CateringItemTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($cateringItemPersisted->id()->equals($lastEvent->cateringItemId()));
    }

    public function testIsHandle(): void
    {
        $repository = CateringItemRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterCateringItemRequest(
            name: 'test',
            category: CateringItemCategory::Breakfast,
            unit: Unit::Kilogram,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CateringItemRepository $repository, Locale $locale): RegisterCateringItemService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterCateringItemService(
            cateringItemRepository: $repository,
            authorizer: new CateringItemAuthorizer(),
        );
    }
}
