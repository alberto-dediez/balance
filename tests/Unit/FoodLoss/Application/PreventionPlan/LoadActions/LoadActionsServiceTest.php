<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\LoadActions;

use App\FoodLoss\Application\PreventionPlan\LoadActions\LoadActionsRequest;
use App\FoodLoss\Application\PreventionPlan\LoadActions\LoadActionsService;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionLoaded;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionStatus;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionAdded;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionRemoved;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionsLoader;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedActionSuggestion;
use App\FoodLoss\Infrastructure\AI\FakeProposedActionsLoader;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class LoadActionsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(PreventionPlanRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new LoadActionsRequest(
            preventionPlanId: PreventionPlanMother::registeredPreventionPlan()->id(),
        )));
    }

    public function testMarksVersionAsLoadedWhenNoPropertiesNeedSuggestions(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();
        $plan = PreventionPlanMother::registeredPreventionPlan();
        $repository->add($plan);

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $this->createSUT($repository)(new LoadActionsRequest($plan->id()), UserMother::adminUser());

        $this->assertSame(PreventionPlanVersionStatus::Loaded, $plan->currentPlanVersion()->status());
        $this->assertInstanceOf(PreventionPlanVersionLoaded::class, $this->spy->lastEvent());
    }

    public function testAddsActionsForPropertiesWithoutSuggestions(): void
    {
        $propertyId = new PropertyId('property-1');
        $actionId = new ActionId('action-suggested');
        $criticalPointId = CriticalPointId::generate();

        $repository = PreventionPlanRepositoryMother::inMemory();
        $plan = PreventionPlanMother::withPropertyIds([$propertyId]);
        $repository->add($plan);

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $loader = $this->loaderReturning([(string) $propertyId => [new ProposedActionSuggestion($actionId, $criticalPointId)]]);

        $this->createSUT($repository, $loader)(new LoadActionsRequest($plan->id()), UserMother::adminUser());

        $this->assertTrue($plan->currentPlanVersion()->proposedActions()->has($propertyId, $actionId, $criticalPointId));

        $events = $this->spy->events();
        $addedEvents = array_values(array_filter($events, fn($e) => $e instanceof ProposedActionAdded));
        $this->assertCount(1, $addedEvents);
        $this->assertSame(PreventionPlanVersionStatus::Loaded, $plan->currentPlanVersion()->status());
    }

    public function testSkipsPropertiesThatAlreadyHaveActions(): void
    {
        $propertyWithAction = new PropertyId('property-with-action');
        $propertyWithout = new PropertyId('property-without');
        $existingActionId = new ActionId('action-existing');
        $suggestedActionId = new ActionId('action-suggested');
        $criticalPointId = CriticalPointId::generate();
        $suggestedCriticalPointId = CriticalPointId::generate();

        $repository = PreventionPlanRepositoryMother::inMemory();
        $plan = PreventionPlanMother::withPropertyIds([$propertyWithAction, $propertyWithout]);
        $plan->addProposedAction($propertyWithAction, $existingActionId, $criticalPointId);
        $repository->add($plan);

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
        $loader = new FakeProposedActionsLoader([(string) $propertyWithout => [new ProposedActionSuggestion($suggestedActionId, $suggestedCriticalPointId)]]);

        $this->createSUT($repository, $loader)(new LoadActionsRequest($plan->id()), UserMother::adminUser());

        $this->assertSame([(string) $propertyWithout], $loader->calledWith());
        $this->assertTrue($plan->currentPlanVersion()->proposedActions()->has($propertyWithout, $suggestedActionId, $suggestedCriticalPointId));
        $this->assertTrue($plan->currentPlanVersion()->proposedActions()->has($propertyWithAction, $existingActionId, $criticalPointId));
    }

    public function testRemovesProposedActionsAlreadyImplementedInCriticalPoint(): void
    {
        $propertyId = new PropertyId('property-1');
        $implementedActionId = new ActionId('action-implemented');
        $criticalPointId = CriticalPointId::generate();

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPoint = CriticalPointMother::withImplementedAction($propertyId, $criticalPointId, $implementedActionId);
        $criticalPointRepository->add($criticalPoint);

        $repository = PreventionPlanRepositoryMother::inMemory();
        $plan = PreventionPlanMother::withPropertyIds([$propertyId]);
        $plan->addProposedAction($propertyId, $implementedActionId, $criticalPointId);
        $repository->add($plan);

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $this->createSUT($repository, null, $criticalPointRepository)(new LoadActionsRequest($plan->id()), UserMother::adminUser());

        $this->assertFalse($plan->currentPlanVersion()->proposedActions()->has($propertyId, $implementedActionId, $criticalPointId));

        $events = $this->spy->events();
        $removedEvents = array_values(array_filter($events, fn($e) => $e instanceof ProposedActionRemoved));
        $this->assertCount(1, $removedEvents);
    }

    public function testDoesNothingWhenPlanNotFound(): void
    {
        $repository = PreventionPlanRepositoryMother::inMemory();
        $missingId = PreventionPlanMother::registeredPreventionPlan()->id();

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $this->createSUT($repository)(new LoadActionsRequest($missingId), UserMother::adminUser());

        $this->assertEmpty($this->spy->events());
    }

    private function createSUT(PreventionPlanRepository $repository, ?ProposedActionsLoader $loader = null, ?CriticalPointRepository $criticalPointRepository = null): LoadActionsService
    {
        return new LoadActionsService(
            preventionPlanRepository: $repository,
            proposedActionsLoader: $loader ?? $this->loaderReturning([]),
            criticalPointRepository: $criticalPointRepository ?? CriticalPointRepositoryMother::inMemory(),
        );
    }

    /** @param array<string, ProposedActionSuggestion[]> $suggestions */
    private function loaderReturning(array $suggestions): ProposedActionsLoader
    {
        return new class ($suggestions) implements ProposedActionsLoader {
            /** @param array<string, ProposedActionSuggestion[]> $suggestions */
            public function __construct(private array $suggestions) {}

            public function suggestFor(PropertyId $propertyId): array
            {
                return $this->suggestions[(string) $propertyId] ?? [];
            }
        };
    }
}
