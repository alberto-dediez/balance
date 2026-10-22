<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReopenPropertyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private ReopenPropertyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new ReopenPropertyCarbonFootprintService($this->repository, new PropertyCarbonFootprintAuthorizer());
    }

    public function testOwnerReopensACompletedFootprint(): void
    {
        $footprint = $this->completedFootprint();
        $this->repository->add($footprint);

        ($this->sut)(new ReopenPropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
        $this->assertSame('0', $footprint->totalEmissions()->co2eq());
    }

    public function testAdminReopensACompletedFootprint(): void
    {
        $footprint = $this->completedFootprint();
        $this->repository->add($footprint);

        ($this->sut)(new ReopenPropertyCarbonFootprintRequest($footprint->id()), UserMother::adminUser());

        $this->assertFalse($footprint->isCompleted());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->completedFootprint();
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ReopenPropertyCarbonFootprintRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testNotCompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ReopenPropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ReopenPropertyCarbonFootprintRequest(PropertyCarbonFootprintId::generate()), $this->ownerUser());
    }

    private function completedFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
