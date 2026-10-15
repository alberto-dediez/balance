<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\RefrigerantGasAnswerHandler;
use App\CarbonFootprint\Application\Activity\RefrigerantGasEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityUpdated;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RefrigerantGasAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private RefrigerantGas $refrigerantGas;

    private InMemoryActivityRepository $activityRepository;

    private RefrigerantGasAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->refrigerantGas = RefrigerantGasMother::registeredRefrigerantGas();
        $this->refrigerantGas->setEmissionFactor(ActivityMother::emissionFactor());

        $refrigerantGasRepository = RefrigerantGasRepositoryMother::inMemory();
        $refrigerantGasRepository->add($this->refrigerantGas);

        $selector = new RefrigerantGasEmissionFactorSelector($refrigerantGasRepository);
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new RefrigerantGasAnswerHandler(
            new RegisterRefrigerantGasActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdateRefrigerantGasActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsRefrigerantGas(): void
    {
        $this->assertSame(ActivityType::RefrigerantGas, $this->sut->activityType());
    }

    public function testRegisterDelegatesAndReturnsActivityId(): void
    {
        $activityId = $this->sut->register(
            new RefrigerantGasAnswer(
                refrigerantGasId: $this->refrigerantGas->id(),
                quantity: ActivityMother::quantity(Unit::Kilogram, 2.0),
                source: 'Sala de máquinas',
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);

        $this->assertInstanceOf(RefrigerantGasActivity::class, $activity);
        $this->assertSame('account-id', (string) $activity->accountId());
        $this->assertSame('Sala de máquinas', $activity->source());
    }

    public function testUpdateDelegates(): void
    {
        $activity = RefrigerantGasActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new RefrigerantGasAnswer(
                refrigerantGasId: $this->refrigerantGas->id(),
                quantity: ActivityMother::quantity(Unit::Kilogram, 5.0),
                existingActivityId: $activity->id(),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(RefrigerantGasActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame(5.0, $activity->quantity()->value());
    }
}
