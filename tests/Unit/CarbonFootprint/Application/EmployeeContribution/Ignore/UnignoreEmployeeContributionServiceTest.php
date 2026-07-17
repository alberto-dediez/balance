<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\Ignore;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\CarbonFootprint\Application\EmployeeContribution\Ignore\UnignoreEmployeeContributionRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Ignore\UnignoreEmployeeContributionService;
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

class UnignoreEmployeeContributionServiceTest extends TestCase
{
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private UnignoreEmployeeContributionService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new UnignoreEmployeeContributionService(
            $this->employeeContributionRepository,
            new EmployeeContributionAuthorizer(),
        );
    }

    public function testUnignoringAContributionRestoresItToPending(): void
    {
        $contribution = $this->ignoredContribution();

        ($this->sut)(new UnignoreEmployeeContributionRequest($contribution->id()), $this->ownerUser());

        $unignored = $this->employeeContributionRepository->find($contribution->id());
        $this->assertNotNull($unignored);
        $this->assertTrue($unignored->status()->isPending());
    }

    public function testUnignoringAnAnsweredContributionRestoresItToAnswered(): void
    {
        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $contribution->answer();
        $contribution->ignore();
        $this->employeeContributionRepository->add($contribution);

        ($this->sut)(new UnignoreEmployeeContributionRequest($contribution->id()), $this->ownerUser());

        $unignored = $this->employeeContributionRepository->find($contribution->id());
        $this->assertNotNull($unignored);
        $this->assertTrue($unignored->status()->isAnswered());
    }

    public function testMissingContributionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new UnignoreEmployeeContributionRequest(EmployeeContributionId::generate()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = $this->ignoredContribution();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new UnignoreEmployeeContributionRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function ignoredContribution(): EmployeeContribution
    {
        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $contribution->ignore();
        $this->employeeContributionRepository->add($contribution);

        return $contribution;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
