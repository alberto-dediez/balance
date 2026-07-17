<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\Resend;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\CarbonFootprint\Application\EmployeeContribution\Resend\ResendEmployeeContributionNotificationRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Resend\ResendEmployeeContributionNotificationService;
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

class ResendEmployeeContributionNotificationServiceTest extends TestCase
{
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private ResendEmployeeContributionNotificationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new ResendEmployeeContributionNotificationService(
            $this->employeeContributionRepository,
            new EmployeeContributionAuthorizer(),
        );
    }

    public function testResendingNotificationUpdatesTheContribution(): void
    {
        $contribution = $this->registeredContribution();
        $updatedAtBefore = $contribution->updatedAt();

        ($this->sut)(new ResendEmployeeContributionNotificationRequest($contribution->id()), $this->ownerUser());

        $updated = $this->employeeContributionRepository->find($contribution->id());
        $this->assertNotNull($updated);
        $this->assertNotEquals($updatedAtBefore, $updated->updatedAt());
    }

    public function testMissingContributionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ResendEmployeeContributionNotificationRequest(EmployeeContributionId::generate()), $this->ownerUser());
    }

    public function testIgnoredContributionThrows(): void
    {
        $contribution = $this->registeredContribution();
        $contribution->ignore();
        $this->employeeContributionRepository->add($contribution);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ResendEmployeeContributionNotificationRequest($contribution->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = $this->registeredContribution();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new ResendEmployeeContributionNotificationRequest($contribution->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
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
