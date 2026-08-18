<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Adjust;

use App\Balance\Application\LedgerEntry\Adjust\RecordLedgerAdjustmentRequest;
use App\Balance\Application\LedgerEntry\Adjust\RecordLedgerAdjustmentService;
use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RecordLedgerAdjustmentServiceTest extends TestCase
{
    private InMemoryLedgerEntryRepository $ledger;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->ledger = LedgerEntryRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItRecordsANegativeAdjustmentAgainstABeneficiary(): void
    {
        $accountId = AccountId::generate();

        $response = $this->service()(
            new RecordLedgerAdjustmentRequest(
                beneficiary: LedgerBeneficiary::Account,
                beneficiaryId: (string) $accountId,
                amount: -12.5,
                concept: 'Contracargo de la aportación 123',
            ),
            UserMother::adminUser(),
        );

        $ledgerEntry = $this->ledger->find($response->ledgerEntryId);

        $this->assertNotNull($ledgerEntry);
        $this->assertSame(LedgerEntryType::Adjustment, $ledgerEntry->type());
        $this->assertSame(-12.5, $ledgerEntry->amount());
        $this->assertSame('Contracargo de la aportación 123', $ledgerEntry->concept());
        $this->assertSame(-12.5, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $accountId)->accruedAmount());
    }

    public function testItInheritsTheContextOfTheReversedEntry(): void
    {
        $reversed = LedgerEntryMother::unassigned(16.0);
        $this->ledger->add($reversed);

        $response = $this->service()(
            new RecordLedgerAdjustmentRequest(
                beneficiary: LedgerBeneficiary::Unassigned,
                beneficiaryId: null,
                amount: -16.0,
                concept: 'Reversión de toneladas sin respaldo',
                reversesEntryId: $reversed->id(),
            ),
            UserMother::adminUser(),
        );

        $ledgerEntry = $this->ledger->find($response->ledgerEntryId);

        $this->assertNotNull($ledgerEntry);
        $this->assertSame((string) $reversed->id(), (string) $ledgerEntry->reversesEntryId());
        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());
    }

    public function testItRejectsAnUnknownReversedEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new RecordLedgerAdjustmentRequest(
                beneficiary: LedgerBeneficiary::Unassigned,
                beneficiaryId: null,
                amount: -16.0,
                concept: 'Reversión',
                reversesEntryId: LedgerEntryMother::unassigned()->id(),
            ),
            UserMother::adminUser(),
        );
    }

    public function testItRejectsAReversalAgainstADifferentBeneficiary(): void
    {
        $reversed = LedgerEntryMother::forAccount(AccountId::generate());
        $this->ledger->add($reversed);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new RecordLedgerAdjustmentRequest(
                beneficiary: LedgerBeneficiary::Bioscore,
                beneficiaryId: null,
                amount: -48.0,
                concept: 'Reversión con beneficiario incoherente',
                reversesEntryId: $reversed->id(),
            ),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotRecordAdjustments(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new RecordLedgerAdjustmentRequest(
                beneficiary: LedgerBeneficiary::Account,
                beneficiaryId: (string) AccountId::generate(),
                amount: 10.0,
                concept: 'No permitido',
            ),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): RecordLedgerAdjustmentService
    {
        return new RecordLedgerAdjustmentService($this->ledger, new LedgerEntryAuthorizer());
    }
}
