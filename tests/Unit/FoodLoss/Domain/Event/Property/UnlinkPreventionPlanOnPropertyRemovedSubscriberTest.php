<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Event\Property\UnlinkPreventionPlanOnPropertyRemovedSubscriber;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRemoved;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use PHPUnit\Framework\TestCase;

class UnlinkPreventionPlanOnPropertyRemovedSubscriberTest extends TestCase
{
    private UnlinkPreventionPlanOnPropertyRemovedSubscriber $sut;

    private PreventionPlanRepository $preventionPlanRepository;
    private PropertyId $propertyId;
    private PropertyRemoved $event;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyId = PropertyId::generate();
        $this->event = new PropertyRemoved($this->propertyId);

        $this->preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();

        $this->sut = new UnlinkPreventionPlanOnPropertyRemovedSubscriber(
            $this->preventionPlanRepository,
        );
    }

    public function testIsSubscribedToPropertyRemoved(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new PreventionPlanRemoved(preventionPlanId: PreventionPlanId::generate())));
    }

    public function testUnlinksPropertyWhenPlanHasMultipleProperties(): void
    {
        $otherPropertyId = PropertyId::generate();

        $plan = PreventionPlanMother::withPropertyIds([$this->propertyId, $otherPropertyId]);
        $this->preventionPlanRepository->add($plan);

        $this->sut->handle($this->event);

        $updated = $this->preventionPlanRepository->find($plan->id());

        $this->assertNotNull($updated);
        $this->assertFalse($updated->isRemoved());

        $remainingIds = array_map(static fn($id) => (string) $id, $updated->propertyIds());
        $this->assertNotContains((string) $this->propertyId, $remainingIds);
        $this->assertContains((string) $otherPropertyId, $remainingIds);
    }

    public function testRemovesPlanWhenItOnlyHasTheRemovedProperty(): void
    {
        $plan = PreventionPlanMother::withPropertyIds([$this->propertyId]);
        $this->preventionPlanRepository->add($plan);

        $this->sut->handle($this->event);

        $this->assertNull($this->preventionPlanRepository->find($plan->id()));
    }

    public function testDoesNothingWhenNoPlansAssociatedToProperty(): void
    {
        $plan = PreventionPlanMother::withPropertyIds([]);
        $this->preventionPlanRepository->add($plan);

        $this->sut->handle($this->event);

        $this->assertNotNull($this->preventionPlanRepository->find($plan->id()));
        $this->assertFalse($this->preventionPlanRepository->find($plan->id())->isRemoved());
    }
}
