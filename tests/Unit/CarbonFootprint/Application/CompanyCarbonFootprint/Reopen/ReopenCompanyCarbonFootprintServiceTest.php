<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReopenCompanyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private ReopenCompanyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new ReopenCompanyCarbonFootprintService($this->repository, new CompanyCarbonFootprintAuthorizer());
    }

    public function testOwnerReopensACompletedFootprint(): void
    {
        $footprint = $this->completedFootprint();
        $this->repository->add($footprint);

        ($this->sut)(new ReopenCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, $footprint->status());
        $this->assertSame('0', $footprint->totalEmissions()->co2eq());
    }

    public function testAdminReopensACompletedFootprint(): void
    {
        $footprint = $this->completedFootprint();
        $this->repository->add($footprint);

        ($this->sut)(new ReopenCompanyCarbonFootprintRequest($footprint->id()), UserMother::adminUser());

        $this->assertFalse($footprint->isCompleted());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->completedFootprint();
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ReopenCompanyCarbonFootprintRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testNotCompletedFootprintThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ReopenCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ReopenCompanyCarbonFootprintRequest(CompanyCarbonFootprintId::generate()), $this->ownerUser());
    }

    private function completedFootprint(): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
