<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\PurchaseServiceAnswerHandler;
use App\CarbonFootprint\Application\Activity\PurchaseServiceEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\PurchaseServiceAnswer;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityUpdated;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PurchaseServiceAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private PurchaseService $purchaseService;

    private InMemoryActivityRepository $activityRepository;

    private PurchaseServiceAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->purchaseService = PurchaseServiceMother::registeredPurchaseService();
        $this->purchaseService->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Euro));

        $purchaseServiceRepository = PurchaseServiceRepositoryMother::inMemory();
        $purchaseServiceRepository->add($this->purchaseService);

        $selector = new PurchaseServiceEmissionFactorSelector($purchaseServiceRepository);
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new PurchaseServiceAnswerHandler(
            new RegisterPurchaseServiceActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdatePurchaseServiceActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsPurchaseService(): void
    {
        $this->assertSame(ActivityType::PurchaseService, $this->sut->activityType());
    }

    public function testRegisterDelegatesAndReturnsActivityId(): void
    {
        $activityId = $this->sut->register(
            new PurchaseServiceAnswer(
                purchaseServiceId: $this->purchaseService->id(),
                quantity: ActivityMother::quantity(Unit::Euro, 100.0),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);

        $this->assertInstanceOf(PurchaseServiceActivity::class, $activity);
        $this->assertSame('account-id', (string) $activity->accountId());
        $this->assertTrue($this->purchaseService->id()->equals($activity->purchaseServiceId()));
    }

    public function testUpdateDelegates(): void
    {
        $activity = PurchaseServiceActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new PurchaseServiceAnswer(
                purchaseServiceId: $this->purchaseService->id(),
                quantity: ActivityMother::quantity(Unit::Euro, 500.0),
                existingActivityId: $activity->id(),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(PurchaseServiceActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(500.0, $activity->quantity()->value());
    }
}
