<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\CateringItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\MerchandisingItemEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityService;
use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Application\Activity\Sync\ActivityRowHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Sync\CateringItemActivityRowHandler;
use App\CarbonFootprint\Application\Activity\Sync\MaterialActivityRowHandler;
use App\CarbonFootprint\Application\Activity\Sync\MerchandisingItemActivityRowHandler;
use App\CarbonFootprint\Application\Activity\Sync\PhaseActivitiesSynchronizer;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityService;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials\AnswerMaterialsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials\AnswerMaterialsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnswerMaterialsServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private Material $material;
    private CateringItem $cateringItem;
    private MerchandisingItem $merchandisingItem;
    private AnswerMaterialsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        $this->material = MaterialMother::registeredMaterial();
        $this->material->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026), ActivityMother::emissionFactor()->withYear(2026));
        $materialRepository = MaterialRepositoryMother::inMemory();
        $materialRepository->add($this->material);

        $this->cateringItem = CateringItemMother::registeredCateringItem();
        $this->cateringItem->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026), ActivityMother::emissionFactor()->withYear(2026));
        $cateringItemRepository = CateringItemRepositoryMother::inMemory();
        $cateringItemRepository->add($this->cateringItem);

        $this->merchandisingItem = MerchandisingItemMother::registeredMerchandisingItem();
        $this->merchandisingItem->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026), ActivityMother::emissionFactor()->withYear(2026));
        $merchandisingItemRepository = MerchandisingItemRepositoryMother::inMemory();
        $merchandisingItemRepository->add($this->merchandisingItem);

        $authorizer = new ActivityAuthorizer();
        $calculator = new EmissionsCalculator();
        $materialSelector = new MaterialEmissionFactorSelector($materialRepository);
        $cateringItemSelector = new CateringItemEmissionFactorSelector($cateringItemRepository);
        $merchandisingItemSelector = new MerchandisingItemEmissionFactorSelector($merchandisingItemRepository);

        $handlers = new ActivityRowHandlerRegistry([
            new MaterialActivityRowHandler(
                new RegisterMaterialActivityService($this->activityRepository, $authorizer, $materialSelector, $calculator),
                new UpdateMaterialActivityService($this->activityRepository, $authorizer, $materialSelector, $calculator),
            ),
            new CateringItemActivityRowHandler(
                new RegisterCateringItemActivityService($this->activityRepository, $authorizer, $cateringItemSelector, $calculator),
                new UpdateCateringItemActivityService($this->activityRepository, $authorizer, $cateringItemSelector, $calculator),
            ),
            new MerchandisingItemActivityRowHandler(
                new RegisterMerchandisingItemActivityService($this->activityRepository, $authorizer, $merchandisingItemSelector, $calculator),
                new UpdateMerchandisingItemActivityService($this->activityRepository, $authorizer, $merchandisingItemSelector, $calculator),
            ),
        ]);

        $this->sut = new AnswerMaterialsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            new PhaseActivitiesSynchronizer($handlers),
        );
    }

    public function testRegistersRowsOfEveryTypeAndAnswersMaterials(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), [
            $this->row(ActivityType::Material, (string) $this->material->id()),
            $this->row(ActivityType::CateringItem, (string) $this->cateringItem->id()),
            $this->row(ActivityType::MerchandisingItem, (string) $this->merchandisingItem->id()),
        ]), $this->ownerUser());

        $this->assertSame(EventCarbonFootprintStatus::MaterialsAnswered, $eventCarbonFootprint->status());

        $activities = $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->toArray();
        $this->assertCount(3, $activities);

        $classes = [];
        foreach ($activities as $link) {
            $this->assertSame(Source::Materials, $link->source());
            $activity = $this->activityRepository->findById($link->activityId());
            $this->assertNotNull($activity);
            $classes[] = $activity::class;
        }

        $this->assertContains(MaterialActivity::class, $classes);
        $this->assertContains(CateringItemActivity::class, $classes);
        $this->assertContains(MerchandisingItemActivity::class, $classes);
    }

    public function testCalculatesEmissionsFromFactorAndQuantity(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), [
            $this->row(ActivityType::Material, (string) $this->material->id()),
        ]), $this->ownerUser());

        $activityId = $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->activityIds()[0];
        $activity = $this->activityRepository->findById($activityId);

        $this->assertNotNull($activity);
        $this->assertSame('15', $activity->emissions()->co2eq());
    }

    public function testForcesTheItemUnitOverTheSubmittedOne(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), [
            new ActivityRow(
                activityType: ActivityType::Material,
                itemId: (string) $this->material->id(),
                condition: ItemCondition::New,
                quantity: new Quantity(10.0, Unit::Liter),
            ),
        ]), $this->ownerUser());

        $activityId = $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->activityIds()[0];
        $activity = $this->activityRepository->findById($activityId);

        $this->assertNotNull($activity);
        $this->assertSame(Unit::Kilogram, $activity->quantity()->unit());
    }

    public function testUpdatesExistingRowKeepingItsActivity(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $user = $this->ownerUser();

        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), [
            $this->row(ActivityType::Material, (string) $this->material->id()),
        ]), $user);
        $activityId = $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->activityIds()[0];

        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), [
            $this->row(ActivityType::Material, (string) $this->material->id(), quantity: 20.0, activityId: $activityId),
        ]), $user);

        $activityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityId->equals($activityIds[0]));

        $activity = $this->activityRepository->findById($activityId);
        $this->assertNotNull($activity);
        $this->assertSame(20.0, $activity->quantity()->value());
        $this->assertSame('30', $activity->emissions()->co2eq());
    }

    public function testUnlinksActivitiesMissingFromRows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $user = $this->ownerUser();

        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), [
            $this->row(ActivityType::Material, (string) $this->material->id()),
        ]), $user);
        ($this->sut)(new AnswerMaterialsRequest($eventCarbonFootprint->id(), []), $user);

        $this->assertCount(0, $eventCarbonFootprint->activities()->filterByPhase(Phase::Materials)->toArray());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new AnswerMaterialsRequest($eventCarbonFootprint->id(), []),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new AnswerMaterialsRequest(EventCarbonFootprintMother::random()->id())));
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private function row(ActivityType $activityType, string $itemId, float $quantity = 10.0, ?ActivityId $activityId = null): ActivityRow
    {
        return new ActivityRow(
            activityType: $activityType,
            itemId: $itemId,
            condition: ItemCondition::New,
            quantity: new Quantity($quantity, Unit::Kilogram),
            activityId: $activityId,
        );
    }
}
