<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ElectricityAnswerHandler;
use App\CarbonFootprint\Application\Activity\ElectricityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryGdOFactorRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ElectricityAnswerHandlerTest extends TestCase
{
    private ElectricityMarketer $electricityMarketer;
    private GdOFactor $gdOFactor;
    private ElectricityAnswerHandler $sut;
    private InMemoryActivityRepository $activityRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = new InMemoryActivityRepository();

        $this->electricityMarketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $this->electricityMarketer->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026));
        $electricityMarketerRepository = new InMemoryElectricityMarketerRepository();
        $electricityMarketerRepository->add($this->electricityMarketer);

        $this->gdOFactor = GdOFactorMother::registeredGdOFactor();
        $this->gdOFactor->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026));
        $gdOFactorRepository = new InMemoryGdOFactorRepository();
        $gdOFactorRepository->add($this->gdOFactor);

        $authorizer = new ActivityAuthorizer();
        $calculator = new EmissionsCalculator();
        $selector = new ElectricityEmissionFactorSelector($electricityMarketerRepository, $gdOFactorRepository);

        $this->sut = new ElectricityAnswerHandler(
            new RegisterElectricityActivityService($this->activityRepository, $authorizer, $selector, $calculator),
            new UpdateElectricityActivityService($this->activityRepository, $authorizer, $selector, $calculator),
        );
    }

    public function testRegistersAnActivityFromTheAnswer(): void
    {
        $answer = new ElectricityAnswer(
            electricityMarketerId: $this->electricityMarketer->id(),
            gdOFactorId: $this->gdOFactor->id(),
            quantity: new Quantity(10.0, Unit::KWh),
        );

        $activityId = $this->sut->register($answer, 2026, EmissionFactorGroupMother::defaultId(), $this->ownerUser());

        $activity = $this->activityRepository->findById($activityId);
        $this->assertInstanceOf(ElectricityActivity::class, $activity);
        $this->assertNotNull($activity->gdOFactorId());
        $this->assertTrue($this->gdOFactor->id()->equals($activity->gdOFactorId()));
    }

    public function testUpdatesAnExistingActivityFromTheAnswer(): void
    {
        $registerAnswer = new ElectricityAnswer(
            electricityMarketerId: $this->electricityMarketer->id(),
            gdOFactorId: $this->gdOFactor->id(),
            quantity: new Quantity(10.0, Unit::KWh),
        );
        $activityId = $this->sut->register($registerAnswer, 2026, EmissionFactorGroupMother::defaultId(), $this->ownerUser());

        $updateAnswer = new ElectricityAnswer(
            electricityMarketerId: $this->electricityMarketer->id(),
            gdOFactorId: $this->gdOFactor->id(),
            quantity: new Quantity(30.0, Unit::KWh),
            existingActivityId: $activityId,
        );
        $this->sut->update($updateAnswer, 2026, EmissionFactorGroupMother::defaultId(), $this->ownerUser());

        $activity = $this->activityRepository->findById($activityId);
        $this->assertNotNull($activity);
        $this->assertSame(30.0, $activity->quantity()->value());
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
