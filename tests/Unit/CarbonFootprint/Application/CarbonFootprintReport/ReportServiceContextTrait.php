<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

trait ReportServiceContextTrait
{
    private InMemoryCarbonFootprintReportRepository $reportRepository;
    private InMemoryAccountRepository $accountRepository;

    private function initReportContext(): void
    {
        $this->reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $this->accountRepository = AccountRepositoryMother::inMemory();
    }

    private function givenAccount(bool $canEditTexts, bool $autoReview, string $id = 'account-id'): AccountId
    {
        $this->accountRepository->add(AccountMother::withReportPermissions('MyCompany', $id, $canEditTexts, $autoReview));

        return new AccountId($id);
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
}
