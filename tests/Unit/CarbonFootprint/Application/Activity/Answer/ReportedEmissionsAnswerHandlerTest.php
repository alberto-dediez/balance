<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ReportedEmissionsAnswerHandler;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityUpdated;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ReportedEmissionsAnswerHandlerTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryActivityRepository $activityRepository;

    private ReportedEmissionsAnswerHandler $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $selector = new ReportedEmissionsEmissionFactorSelector();
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new ReportedEmissionsAnswerHandler(
            new RegisterReportedEmissionsActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
            new UpdateReportedEmissionsActivityService($this->activityRepository, new ActivityAuthorizer(), $selector, EmissionsCalculatorMother::create()),
        );
    }

    public function testActivityTypeIsReportedEmissions(): void
    {
        $this->assertSame(ActivityType::ReportedEmissions, $this->sut->activityType());
    }

    public function testRegisterInKilograms(): void
    {
        $activity = $this->register(new Quantity(3200.0, Unit::Kilogram));

        $this->assertSame('Reparto 2025', $activity->description());
        $this->assertSame(3200.0, (float) $activity->emissions()->co2eq());
        $this->assertSame('account-id', (string) $activity->accountId());
    }

    public function testRegisterInTonnesConvertsEmissionsToKilograms(): void
    {
        $activity = $this->register(new Quantity(1.2, Unit::Tonne));

        $this->assertSame(1200.0, (float) $activity->emissions()->co2eq());
        $this->assertSame(Unit::Kilogram, $activity->quantity()->unit());
        $this->assertSame(1200.0, $activity->quantity()->value());
    }

    public function testRegisterInGramsThrows(): void
    {
        $this->expectException(IncompatibleUnitsException::class);

        $this->register(new Quantity(500.0, Unit::Gram));
    }

    public function testUpdateDelegates(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();
        $this->activityRepository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut->update(
            new ReportedEmissionsAnswer(
                description: 'Última milla',
                quantity: new Quantity(0.8, Unit::Tonne),
                existingActivityId: $activity->id(),
            ),
            2024,
            EmissionFactorGroupMother::defaultId(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(ReportedEmissionsActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Última milla', $activity->description());
        $this->assertSame(800.0, (float) $activity->emissions()->co2eq());
    }

    private function register(Quantity $quantity): ReportedEmissionsActivity
    {
        $activityId = $this->sut->register(
            new ReportedEmissionsAnswer(description: 'Reparto 2025', quantity: $quantity),
            2024,
            EmissionFactorGroupMother::defaultId(),
            new AccountId('account-id'),
            UserMother::adminUser(),
        );

        $activity = $this->activityRepository->findById($activityId);
        $this->assertInstanceOf(ReportedEmissionsActivity::class, $activity);

        return $activity;
    }
}
