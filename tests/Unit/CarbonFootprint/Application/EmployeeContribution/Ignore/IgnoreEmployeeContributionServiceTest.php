<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\Ignore;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\CarbonFootprint\Application\EmployeeContribution\Ignore\IgnoreEmployeeContributionRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Ignore\IgnoreEmployeeContributionService;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class IgnoreEmployeeContributionServiceTest extends TestCase
{
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private IgnoreEmployeeContributionService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new IgnoreEmployeeContributionService(
            $this->employeeContributionRepository,
            new EmployeeContributionAuthorizer(),
        );
    }

    public function testIgnoringAContributionMarksItAsIgnored(): void
    {
        $contribution = $this->registeredContribution();

        ($this->sut)(new IgnoreEmployeeContributionRequest($contribution->id()), $this->ownerUser());

        $ignored = $this->employeeContributionRepository->find($contribution->id());
        $this->assertNotNull($ignored);
        $this->assertTrue($ignored->status()->isIgnored());
    }

    public function testMissingContributionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new IgnoreEmployeeContributionRequest(EmployeeContributionId::generate()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = $this->registeredContribution();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new IgnoreEmployeeContributionRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function registeredContribution(): EmployeeContribution
    {
        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->employeeContributionRepository->add($contribution);

        return $contribution;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
