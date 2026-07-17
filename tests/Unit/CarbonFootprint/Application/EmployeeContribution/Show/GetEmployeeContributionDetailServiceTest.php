<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\Show;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\CarbonFootprint\Application\EmployeeContribution\Show\GetEmployeeContributionDetailRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Show\GetEmployeeContributionDetailService;
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

class GetEmployeeContributionDetailServiceTest extends TestCase
{
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private GetEmployeeContributionDetailService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new GetEmployeeContributionDetailService(
            $this->employeeContributionRepository,
            new EmployeeContributionAuthorizer(),
        );
    }

    public function testOwnerCanViewContributionForItsAccount(): void
    {
        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $response = ($this->sut)(new GetEmployeeContributionDetailRequest($contribution->id()), $this->ownerUser());

        $this->assertTrue($response->employeeContribution->id()->equals($contribution->id()));
    }

    public function testMissingContributionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetEmployeeContributionDetailRequest(EmployeeContributionId::generate()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetEmployeeContributionDetailRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    public function testOwnerFromAnotherAccountCannotView(): void
    {
        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new GetEmployeeContributionDetailRequest($contribution->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account-id')),
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
