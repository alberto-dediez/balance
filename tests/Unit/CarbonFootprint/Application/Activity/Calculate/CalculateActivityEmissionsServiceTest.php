<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Calculate;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsRequest;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsService;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CalculateActivityEmissionsServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private Material $material;
    private CalculateActivityEmissionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        $this->material = MaterialMother::registeredMaterial();
        $this->material->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026), ActivityMother::emissionFactor()->withYear(2026));
        $materialRepository = MaterialRepositoryMother::inMemory();
        $materialRepository->add($this->material);

        $this->sut = new CalculateActivityEmissionsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            new ActivityEmissionsResolver(
                new EmissionFactorSelectorRegistry([new MaterialEmissionFactorSelector($materialRepository)]),
                EmissionsCalculatorMother::create(),
            ),
        );
    }

    public function testCalculatesEmissionsForTheCalculationYearAndGroup(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $response = ($this->sut)(new CalculateActivityEmissionsRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            activityType: ActivityType::Material,
            itemId: (string) $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ), $this->ownerUser());

        $this->assertSame('15', $response->emissions->co2eq());
        $this->assertSame('1.5', $response->emissionFactor->co2eq());
        $this->assertSame(2026, $response->emissionFactor->year());
    }

    public function testThrowsWhenNoSelectorForActivityType(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new CalculateActivityEmissionsRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            activityType: ActivityType::Waste,
            itemId: (string) $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ), $this->ownerUser());
    }

    public function testThrowsWhenEventCarbonFootprintNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new CalculateActivityEmissionsRequest(
            eventCarbonFootprintId: EventCarbonFootprintMother::random()->id(),
            activityType: ActivityType::Material,
            itemId: (string) $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(new CalculateActivityEmissionsRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            activityType: ActivityType::Material,
            itemId: (string) $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        ), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new CalculateActivityEmissionsRequest(
            eventCarbonFootprintId: EventCarbonFootprintMother::random()->id(),
            activityType: ActivityType::Material,
            itemId: (string) $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        )));
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
}
