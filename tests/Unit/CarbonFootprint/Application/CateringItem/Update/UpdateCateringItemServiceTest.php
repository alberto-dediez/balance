<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem\Update;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\Update\EmissionFactorData;
use App\CarbonFootprint\Application\CateringItem\Update\UpdateCateringItemRequest;
use App\CarbonFootprint\Application\CateringItem\Update\UpdateCateringItemService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemTranslated;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateCateringItemServiceTest extends TestCase
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
        $repository = CateringItemRepositoryMother::inMemory();

        $cateringItem = CateringItemMother::random();

        $repository->add($cateringItem);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateCateringItemRequest(
            cateringItemId: $cateringItem->id(),
            name: 'changedName',
            category: CateringItemCategory::Snack,
            unit: Unit::Piece,
            unitWeight: '0.5',
        );

        $result = $sut($request, $user);

        $cateringItemPersisted = $repository->find($result->cateringItemId);

        $this->assertNotNull($cateringItemPersisted);
        $this->assertSame('0.5', $cateringItemPersisted->unitWeight());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CateringItemUpdated);
        $this->assertTrue($cateringItemPersisted->id()->equals($lastEvent->cateringItemId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = CateringItemRepositoryMother::inMemory(Locale::Spanish);

        $cateringItem = CateringItemMother::random();

        $repository->add($cateringItem);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateCateringItemRequest(
            cateringItemId: $cateringItem->id(),
            name: 'changedName',
            category: CateringItemCategory::Breakfast,
            unit: Unit::Kilogram,
        );

        $result = $sut($request, $user);

        $cateringItemPersisted = $repository->find($result->cateringItemId);

        $this->assertNotNull($cateringItemPersisted);
        $this->assertEquals('changedName', $cateringItemPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CateringItemTranslated);
        $this->assertTrue($cateringItemPersisted->id()->equals($lastEvent->cateringItemId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $cateringItem = CateringItemMother::random();

        $repository->add($cateringItem);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateCateringItemRequest(
            cateringItemId: $cateringItem->id(),
            name: 'changedName',
            category: CateringItemCategory::Snack,
            unit: Unit::Piece,
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $result = $sut($request, $user);

        $cateringItemPersisted = $repository->find($result->cateringItemId);

        $this->assertNotNull($cateringItemPersisted);
        $this->assertCount(1, $cateringItemPersisted->emissionFactors());

        $persistedFactor = $cateringItemPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        // co2eq is respected as provided by the user (non-zero), not recomputed from co2/ch4/n2o.
        $this->assertSame('1.5', $persistedFactor->newFactor()->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->newFactor()->unit());
    }

    private function createSUT(
        CateringItemRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateCateringItemService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateCateringItemService(
            cateringItemRepository: $repository,
            authorizer: new CateringItemAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
