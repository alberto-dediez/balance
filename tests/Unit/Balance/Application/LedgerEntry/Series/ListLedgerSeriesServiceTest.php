<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Series;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Application\LedgerEntry\Series\ListLedgerSeriesRequest;
use App\Balance\Application\LedgerEntry\Series\ListLedgerSeriesService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListLedgerSeriesServiceTest extends TestCase
{
    private InMemoryLedgerEntryRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = LedgerEntryRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAnAdminGetsMonthlySeriesWithAccruedAmountsByBeneficiary(): void
    {
        $this->repository->add(LedgerEntryMother::recorded(
            type: LedgerEntryType::ManagementFee,
            beneficiary: LedgerBeneficiary::Bioscore,
            amount: 20.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        ));
        $this->repository->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) AccountId::generate(),
            amount: 48.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        ));
        $this->repository->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) AccountId::generate(),
            amount: 32.0,
            occurredAt: new \DateTimeImmutable('2026-02-10'),
        ));

        $response = $this->service()(
            new ListLedgerSeriesRequest(
                from: new \DateTimeImmutable('2026-01-01'),
                to: new \DateTimeImmutable('2026-02-28'),
            ),
            UserMother::adminUser(),
        );

        $this->assertSame(['2026-01', '2026-02'], $response->months);
        $this->assertSame([48.0, 32.0], $response->amountsByBeneficiary[LedgerBeneficiary::Account->value]);
        $this->assertSame([20.0, 0.0], $response->amountsByBeneficiary[LedgerBeneficiary::Bioscore->value]);
        $this->assertSame([68.0, 32.0], $response->accruedByMonth);
        $this->assertSame([0.0, 0.0], $response->settledByMonth);
    }

    public function testSettledEntriesAreCountedSeparatelyFromAccrued(): void
    {
        $settled = LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) AccountId::generate(),
            amount: 48.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        );
        $settled->settle(PayoutId::generate());

        $this->repository->add($settled);
        $this->repository->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) AccountId::generate(),
            amount: 32.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        ));

        $response = $this->service()(
            new ListLedgerSeriesRequest(
                from: new \DateTimeImmutable('2026-01-01'),
                to: new \DateTimeImmutable('2026-01-31'),
            ),
            UserMother::adminUser(),
        );

        $this->assertSame([80.0], $response->accruedByMonth);
        $this->assertSame([48.0], $response->settledByMonth);
    }

    public function testTopPendingReturnsBeneficiariesOrderedByPendingAmount(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));
        $this->repository->add(LedgerEntryMother::forProject());

        $response = $this->service()(
            new ListLedgerSeriesRequest(
                from: new \DateTimeImmutable('first day of this month'),
                to: new \DateTimeImmutable('last day of this month'),
                topBeneficiaries: 10,
            ),
            UserMother::adminUser(),
        );

        $amounts = array_map(static fn(array $row): float => $row['amount'], $response->topPending);

        $this->assertSame([80.0, 48.0, 32.0], $amounts);
    }

    public function testEntriesOutsideTheRangeAreExcluded(): void
    {
        $this->repository->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Bioscore,
            amount: 20.0,
            occurredAt: new \DateTimeImmutable('2025-12-10'),
        ));
        $this->repository->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Bioscore,
            amount: 10.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        ));

        $response = $this->service()(
            new ListLedgerSeriesRequest(
                from: new \DateTimeImmutable('2026-01-01'),
                to: new \DateTimeImmutable('2026-01-31'),
            ),
            UserMother::adminUser(),
        );

        $this->assertSame([10.0], $response->accruedByMonth);
    }

    public function testAPlainUserIsNotAuthorized(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ListLedgerSeriesRequest(
                from: new \DateTimeImmutable('2026-01-01'),
                to: new \DateTimeImmutable('2026-01-31'),
            ),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function service(): ListLedgerSeriesService
    {
        return new ListLedgerSeriesService($this->repository, new LedgerEntryAuthorizer());
    }
}
