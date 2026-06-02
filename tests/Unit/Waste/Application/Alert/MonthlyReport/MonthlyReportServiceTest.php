<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\MonthlyReport;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Waste\Application\Alert\MonthlyReport\MonthlyReportRequest;
use App\Waste\Application\Alert\MonthlyReport\MonthlyReportService;
use PHPUnit\Framework\TestCase;

class MonthlyReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsAlertsGroupedByAccount(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromNameAndId('Test Account', 'account-id-test');
        $accountRepository->add($account);

        $alertRepository->add(AlertMother::fromIdAndAccount('alert-1', 'account-id-test'));
        $alertRepository->add(AlertMother::fromIdAndAccount('alert-2', 'account-id-test'));

        $sut = new MonthlyReportService(
            alertRepository: $alertRepository,
            accountRepository: $accountRepository,
        );

        $month = new \DateTimeImmutable('2026-06-01');
        $result = $sut(new MonthlyReportRequest(month: $month), UserMother::adminUser());

        $this->assertEquals(2, $result->totalAlerts);
        $this->assertArrayHasKey('account-id-test', $result->alertsByAccount);
        $this->assertCount(2, $result->alertsByAccount['account-id-test']);
    }

    public function testSkipsAccountsWithNoAlerts(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromNameAndId('Empty Account', 'account-no-alerts');
        $accountRepository->add($account);

        $sut = new MonthlyReportService(
            alertRepository: $alertRepository,
            accountRepository: $accountRepository,
        );

        $month = new \DateTimeImmutable('2026-06-01');
        $result = $sut(new MonthlyReportRequest(month: $month), UserMother::adminUser());

        $this->assertEquals(0, $result->totalAlerts);
        $this->assertEmpty($result->alertsByAccount);
    }

    public function testReturnsAlertsFromMultipleAccounts(): void
    {
        $alertRepository = AlertRepositoryMother::inMemory();
        $accountRepository = AccountRepositoryMother::inMemory();

        $account1 = AccountMother::fromNameAndId('Account One', 'account-one');
        $account2 = AccountMother::fromNameAndId('Account Two', 'account-two');
        $accountRepository->add($account1);
        $accountRepository->add($account2);

        $alertRepository->add(AlertMother::fromIdAndAccount('alert-1', 'account-one'));
        $alertRepository->add(AlertMother::fromIdAndAccount('alert-2', 'account-two'));
        $alertRepository->add(AlertMother::fromIdAndAccount('alert-3', 'account-two'));

        $sut = new MonthlyReportService(
            alertRepository: $alertRepository,
            accountRepository: $accountRepository,
        );

        $month = new \DateTimeImmutable('2026-06-01');
        $result = $sut(new MonthlyReportRequest(month: $month), UserMother::adminUser());

        $this->assertEquals(3, $result->totalAlerts);
        $this->assertCount(2, $result->alertsByAccount);
        $this->assertCount(1, $result->alertsByAccount['account-one']);
        $this->assertCount(2, $result->alertsByAccount['account-two']);
    }
}
