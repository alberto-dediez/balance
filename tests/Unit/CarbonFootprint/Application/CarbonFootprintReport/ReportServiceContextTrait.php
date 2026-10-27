<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;
use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountReportPermissions;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;

trait ReportServiceContextTrait
{
    private InMemoryCarbonFootprintReportRepository $reportRepository;
    private InMemoryAccountRepository $accountRepository;
    private ReportPermissionsPolicy $reportPermissionsPolicy;

    private function initReportContext(): void
    {
        $this->reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $this->accountRepository = AccountRepositoryMother::inMemory();
        $this->reportPermissionsPolicy = new ReportPermissionsPolicy($this->accountRepository, new ImpersonationPolicy($this->accountRepository));
    }

    private function givenAccount(bool $canEditTexts, bool $autoReview, string $id = 'account-id'): AccountId
    {
        $this->accountRepository->add(AccountMother::withReportPermissions('MyCompany', $id, $canEditTexts, $autoReview));

        return new AccountId($id);
    }

    private function givenConsultantAccount(bool $canEditTexts, bool $autoReview, string $id = 'consultant-id'): AccountId
    {
        $account = AccountMother::consultantAccountFromNameAndId('ConsultantAccount', $id);
        $account->changeReportPermissions(new AccountReportPermissions($canEditTexts, $autoReview));
        $this->accountRepository->add($account);

        return $account->id();
    }

    private function givenManagedAccount(AccountId $consultantId, bool $canEditTexts, bool $autoReview, string $id = 'account-id'): AccountId
    {
        $account = AccountMother::withReportPermissions('MyCompany', $id, $canEditTexts, $autoReview);
        $account->changeConsultant($consultantId);
        $this->accountRepository->add($account);

        return $account->id();
    }

    private function impersonatingAuthorizer(AccountId $accountId): CarbonFootprintReportAuthorizer
    {
        return new CarbonFootprintReportAuthorizer(new StubCurrentAccountIdProvider($accountId), new StubCurrentRolesProvider([Role::Owner]));
    }

    private function givenReport(CarbonFootprintReport $report): CarbonFootprintReport
    {
        $this->reportRepository->add($report);

        return $report;
    }

    private function ownerUser(string $accountId = 'account-id'): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    private function consultantOwnerUser(string $accountId = 'consultant-id'): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('consultant', Role::Owner, new AccountId($accountId));
    }

    private function adminUserOfAccount(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, new AccountId($accountId));
    }
}
