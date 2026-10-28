<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityRegistered;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterReportedEmissionsActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activityRepo = ActivityRepositoryMother::inMemory();

        $result = $this->sut($activityRepo)($this->request(new Quantity(1.2, Unit::Tonne)), UserMother::adminUser());

        $activity = $activityRepo->findById($result->activityId);

        $this->assertInstanceOf(ReportedEmissionsActivity::class, $activity);
        $this->assertSame(1200.0, (float) $activity->emissions()->co2eq());
        $this->assertSame('reported', $activity->emissionFactor()->source());
        $this->assertSame(2024, $activity->emissionFactor()->year());
        $this->assertInstanceOf(ReportedEmissionsActivityRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(\Throwable::class);

        $this->sut(ActivityRepositoryMother::inMemory())(
            $this->request(new Quantity(10.0, Unit::Kilogram)),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut(ActivityRepositoryMother::inMemory())->isHandled($this->request(new Quantity(10.0, Unit::Kilogram))));
    }

    private function sut(InMemoryActivityRepository $activityRepository): RegisterReportedEmissionsActivityService
    {
        return new RegisterReportedEmissionsActivityService($activityRepository, new ActivityAuthorizer(), new ReportedEmissionsEmissionFactorSelector(), EmissionsCalculatorMother::create());
    }

    private function request(Quantity $quantity): RegisterReportedEmissionsActivityRequest
    {
        return new RegisterReportedEmissionsActivityRequest(
            accountId: new AccountId('account-id'),
            description: 'Reparto 2025',
            quantity: $quantity,
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }
}
