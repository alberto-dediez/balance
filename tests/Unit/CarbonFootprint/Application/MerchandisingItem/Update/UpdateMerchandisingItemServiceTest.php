<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem\Update;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Application\MerchandisingItem\Update\EmissionFactorData;
use App\CarbonFootprint\Application\MerchandisingItem\Update\UpdateMerchandisingItemRequest;
use App\CarbonFootprint\Application\MerchandisingItem\Update\UpdateMerchandisingItemService;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemTranslated;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMerchandisingItemServiceTest extends TestCase
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
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $merchandisingItem = MerchandisingItemMother::random();

        $repository->add($merchandisingItem);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMerchandisingItemRequest(
            merchandisingItemId: $merchandisingItem->id(),
            name: 'changedName',
            category: MerchandisingItemCategory::Sport,
            unit: Unit::Piece,
            unitWeight: '0.5',
        );

        $result = $sut($request, $user);

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNotNull($merchandisingItemPersisted);
        $this->assertSame('0.5', $merchandisingItemPersisted->unitWeight());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MerchandisingItemUpdated);
        $this->assertTrue($merchandisingItemPersisted->id()->equals($lastEvent->merchandisingItemId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory(Locale::Spanish);

        $merchandisingItem = MerchandisingItemMother::random();

        $repository->add($merchandisingItem);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateMerchandisingItemRequest(
            merchandisingItemId: $merchandisingItem->id(),
            name: 'changedName',
            category: MerchandisingItemCategory::Merchandising,
            unit: Unit::Kilogram,
        );

        $result = $sut($request, $user);

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNotNull($merchandisingItemPersisted);
        $this->assertEquals('changedName', $merchandisingItemPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MerchandisingItemTranslated);
        $this->assertTrue($merchandisingItemPersisted->id()->equals($lastEvent->merchandisingItemId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $merchandisingItem = MerchandisingItemMother::random();

        $repository->add($merchandisingItem);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMerchandisingItemRequest(
            merchandisingItemId: $merchandisingItem->id(),
            name: 'changedName',
            category: MerchandisingItemCategory::Sport,
            unit: Unit::Piece,
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $result = $sut($request, $user);

        $merchandisingItemPersisted = $repository->find($result->merchandisingItemId);

        $this->assertNotNull($merchandisingItemPersisted);
        $this->assertCount(1, $merchandisingItemPersisted->emissionFactors());

        $persistedFactor = $merchandisingItemPersisted->emissionFactorFor(2024);

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    private function createSUT(MerchandisingItemRepository $repository, Locale $locale = Locale::Spanish): UpdateMerchandisingItemService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateMerchandisingItemService(
            merchandisingItemRepository: $repository,
            authorizer: new MerchandisingItemAuthorizer(),
        );
    }
}
