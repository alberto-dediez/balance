<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Summary;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Application\LedgerEntry\Summary\SummarizeLedgerRequest;
use App\Balance\Application\LedgerEntry\Summary\SummarizeLedgerService;
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

class SummarizeLedgerServiceTest extends TestCase
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

    public function testAnAdminGetsTotalsByBeneficiaryAndByType(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $this->repository->add(LedgerEntryMother::forBioscore(20.0, LedgerEntryType::ManagementFee));

        $response = $this->service()(
            new SummarizeLedgerRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(48.0, $response->accruedFor(LedgerBeneficiary::Account));
        $this->assertSame(20.0, $response->accruedFor(LedgerBeneficiary::Bioscore));
        $this->assertSame(48.0, $response->totalFor(LedgerEntryType::ContributionNet));
        $this->assertSame(20.0, $response->totalFor(LedgerEntryType::ManagementFee));
    }

    public function testPendingExcludesSettledEntries(): void
    {
        $settled = LedgerEntryMother::forAccount(AccountId::generate(), 48.0);
        $settled->settle(PayoutId::generate());

        $this->repository->add($settled);
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));

        $response = $this->service()(
            new SummarizeLedgerRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(80.0, $response->accruedFor(LedgerBeneficiary::Account));
        $this->assertSame(32.0, $response->pendingFor(LedgerBeneficiary::Account));
    }

    public function testCollectedAmountSumsAllTypes(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $this->repository->add(LedgerEntryMother::forBioscore(20.0, LedgerEntryType::ManagementFee));

        $response = $this->service()(
            new SummarizeLedgerRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(68.0, $response->collectedAmount());
    }

    public function testAPlainUserIsNotAuthorized(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new SummarizeLedgerRequest(limit: 0),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function service(): SummarizeLedgerService
    {
        return new SummarizeLedgerService($this->repository, new LedgerEntryAuthorizer());
    }
}
