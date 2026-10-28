<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\ActivityNotFoundException;
use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityUpdated;
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

class UpdateReportedEmissionsActivityServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->sut($repository)(
            $this->request($activity->id(), new Quantity(0.8, Unit::Tonne)),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );

        $this->assertInstanceOf(ReportedEmissionsActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('Última milla', $activity->description());
        $this->assertSame(800.0, (float) $activity->emissions()->co2eq());
    }

    public function testUnauthorizedAccess(): void
    {
        $activity = ReportedEmissionsActivityMother::registered();
        $repository = ActivityRepositoryMother::inMemory();
        $repository->save($activity);

        $this->expectException(\Throwable::class);

        $this->sut($repository)(
            $this->request($activity->id(), new Quantity(10.0, Unit::Kilogram)),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    public function testThrowsWhenActivityNotFound(): void
    {
        $this->expectException(ActivityNotFoundException::class);

        $this->sut(ActivityRepositoryMother::inMemory())(
            $this->request(ActivityId::generate(), new Quantity(10.0, Unit::Kilogram)),
            UserMother::adminUser(),
        );
    }

    private function sut(InMemoryActivityRepository $repository): UpdateReportedEmissionsActivityService
    {
        return new UpdateReportedEmissionsActivityService($repository, new ActivityAuthorizer(), new ReportedEmissionsEmissionFactorSelector(), EmissionsCalculatorMother::create());
    }

    private function request(ActivityId $activityId, Quantity $quantity): UpdateReportedEmissionsActivityRequest
    {
        return new UpdateReportedEmissionsActivityRequest(
            activityId: $activityId,
            description: 'Última milla',
            quantity: $quantity,
            year: 2024,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
        );
    }
}
