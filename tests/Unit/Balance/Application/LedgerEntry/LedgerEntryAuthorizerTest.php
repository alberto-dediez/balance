<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class LedgerEntryAuthorizerTest extends TestCase
{
    public function testAdminCanListAndRegisterButNeverEditOrRemove(): void
    {
        $user = UserMother::adminUser();
        $subject = $this->entry(LedgerBeneficiary::Bioscore, null);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerOnlySeesTheEntriesOfTheirOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Account, (string) $accountId),
            Action::List,
            $user,
        ));

        $this->assertFalse($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Account, (string) AccountId::generate()),
            Action::List,
            $user,
        ));
    }

    public function testOwnerNeverSeesBioscoreVatOrUnassignedEntries(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertFalse($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Bioscore, null, LedgerEntryType::Vat),
            Action::List,
            $user,
        ));

        $this->assertFalse($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Bioscore, null, LedgerEntryType::ManagementFee),
            Action::List,
            $user,
        ));

        $this->assertFalse($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Unassigned, null),
            Action::List,
            $user,
        ));
    }

    public function testOwnerCannotRegisterAdjustments(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertFalse($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Account, (string) $accountId, LedgerEntryType::Adjustment),
            Action::Register,
            $user,
        ));
    }

    public function testAPlainUserCannotSeeAnything(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertFalse($this->createSUT()->canDoThat(
            $this->entry(LedgerBeneficiary::Account, (string) AccountId::generate()),
            Action::List,
            $user,
        ));
    }

    private function entry(
        LedgerBeneficiary $beneficiary,
        ?string $beneficiaryId,
        LedgerEntryType $type = LedgerEntryType::ContributionNet,
    ): LedgerEntry {
        return new LedgerEntry(
            id: LedgerEntryId::generate(),
            type: $type,
            beneficiary: $beneficiary,
            beneficiaryId: $beneficiaryId,
        );
    }

    private function createSUT(): LedgerEntryAuthorizer
    {
        return new LedgerEntryAuthorizer();
    }
}
