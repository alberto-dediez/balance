<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\Complete;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompletePropertyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryActivityRepository $activityRepository;
    private CompletePropertyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new CompletePropertyCarbonFootprintService(
            $this->repository,
            new PropertyCarbonFootprintAuthorizer(),
            new EmissionsBreakdownBuilder($this->activityRepository),
        );
    }

    public function testCompletesTheFootprintWithTheTotalOfItsActivities(): void
    {
        $footprint = $this->scope2AnsweredFootprint();
        $activity = CombustibleActivityMother::registered(new AccountId('account-id-test'));
        $this->activityRepository->save($activity);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->repository->add($footprint);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertTrue($footprint->isCompleted());
        $this->assertSame('15', $footprint->totalEmissions()->co2eq());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest(PropertyCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testNotReadyFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $footprint = $this->scope2AnsweredFootprint();
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function scope2AnsweredFootprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::withScope2Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
