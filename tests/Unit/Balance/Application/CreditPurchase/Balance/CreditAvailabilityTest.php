<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\NegativeCreditBalanceException;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class CreditAvailabilityTest extends TestCase
{
    public function testWithoutContributionsEverythingPurchasedIsAvailable(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0));

        $balance = $this->createSUT($creditPurchases)->for($accountId, $projectId);

        $this->assertSame(10.0, $balance->purchasedTonnes());
        $this->assertSame(0.0, $balance->consumedTonnes());
        $this->assertSame(10.0, $balance->availableTonnes());
    }

    public function testPaidAndLiveReservationsBothConsumeCredit(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::paid($accountId, $projectId, tonnes: 3.0));
        $contributions->add(ProjectContributionMother::pending($accountId, $projectId, tonnes: 2.0));

        $balance = $this->createSUT($creditPurchases, $contributions)->for($accountId, $projectId);

        $this->assertSame(5.0, $balance->consumedTonnes());
        $this->assertSame(5.0, $balance->availableTonnes());
    }

    public function testExpiredAndOverdueReservationsReleaseTheirCredit(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::expired($accountId, $projectId, tonnes: 4.0));
        $contributions->add(ProjectContributionMother::overdue($accountId, $projectId, tonnes: 4.0));

        $balance = $this->createSUT($creditPurchases, $contributions)->for($accountId, $projectId);

        $this->assertSame(0.0, $balance->consumedTonnes());
        $this->assertSame(10.0, $balance->availableTonnes());
    }

    public function testContributionsOfAnotherProjectDoNotConsumeCredit(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::paid($accountId, ProjectId::generate(), tonnes: 6.0));

        $balance = $this->createSUT($creditPurchases, $contributions)->for($accountId, $projectId);

        $this->assertSame(10.0, $balance->availableTonnes());
    }

    public function testConsumingMoreThanPurchasedIsRejectedAsABrokenBalance(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::paid($accountId, $projectId, tonnes: 7.0));
        $contributions->add(ProjectContributionMother::pending($accountId, $projectId, tonnes: 4.0));

        $balance = $this->createSUT($creditPurchases, $contributions)->for($accountId, $projectId);

        $this->expectException(NegativeCreditBalanceException::class);

        $balance->availableTonnes();
    }

    public function testAnExhaustedBalanceIsNotMistakenForABrokenOne(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 0.1));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::paid($accountId, $projectId, tonnes: 0.05));
        $contributions->add(ProjectContributionMother::paid($accountId, $projectId, tonnes: 0.05));

        $balance = $this->createSUT($creditPurchases, $contributions)->for($accountId, $projectId);

        $this->assertEqualsWithDelta(0.0, $balance->availableTonnes(), 0.000001);
    }

    public function testForAccountAppliesConsumptionToEveryBalance(): void
    {
        $accountId = AccountId::generate();
        $firstProjectId = ProjectId::generate();
        $secondProjectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $firstProjectId, tonnes: 10.0));
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $secondProjectId, tonnes: 8.0));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::paid($accountId, $firstProjectId, tonnes: 1.5));

        $balances = $this->createSUT($creditPurchases, $contributions)->forAccount($accountId);

        $available = [];

        foreach ($balances as $balance) {
            $available[(string) $balance->projectId()] = $balance->availableTonnes();
        }

        $this->assertSame(8.5, $available[(string) $firstProjectId]);
        $this->assertSame(8.0, $available[(string) $secondProjectId]);
    }

    public function testOwnAvailabilityOnlyCountsTheLotsTheClientPaid(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 4.0));
        $creditPurchases->add(CreditPurchaseMother::validated(
            $accountId,
            $projectId,
            tonnes: 6.0,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
        ));

        $own = $this->createSUT($creditPurchases)->ownFor($accountId, $projectId);

        $this->assertSame(4.0, $own);
    }

    public function testOwnAvailabilityDiscountsWhatIsAlreadyAllocatedToThoseLots(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $ownPurchase = CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0);

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add($ownPurchase);

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add($this->allocatedTo($ownPurchase, $accountId, $projectId, tonnes: 3.0));

        $own = $this->createSUT($creditPurchases, $contributions)->ownFor($accountId, $projectId);

        $this->assertSame(7.0, $own);
    }

    public function testOwnAvailabilityNeverExceedsTheGlobalOne(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0));

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contributions->add(ProjectContributionMother::pending($accountId, $projectId, tonnes: 8.0));

        $own = $this->createSUT($creditPurchases, $contributions)->ownFor($accountId, $projectId);

        $this->assertSame(2.0, $own);
    }

    public function testWithoutOwnLotsThereIsNoOwnAvailability(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated(
            $accountId,
            $projectId,
            tonnes: 10.0,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
        ));

        $own = $this->createSUT($creditPurchases)->ownFor($accountId, $projectId);

        $this->assertSame(0.0, $own);
    }

    private function allocatedTo(
        CreditPurchase $creditPurchase,
        AccountId $accountId,
        ProjectId $projectId,
        float $tonnes,
    ): ProjectContribution {
        $projectContribution = ProjectContributionMother::paid($accountId, $projectId, tonnes: $tonnes);
        $projectContribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $creditPurchase->id(),
                purchaseReference: $creditPurchase->reference(),
                tonnes: $tonnes,
                startTonnes: 0.0,
            ),
        ]);

        return $projectContribution;
    }

    private function createSUT(
        ?CreditPurchaseRepository $creditPurchases = null,
        ?ProjectContributionRepository $contributions = null,
    ): CreditAvailability {
        return new CreditAvailability(
            $creditPurchases ?? CreditPurchaseRepositoryMother::inMemory(),
            $contributions ?? ProjectContributionRepositoryMother::inMemory(),
        );
    }
}
