<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\Balance\CreditSourceSelector;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class CreditSourceSelectorTest extends TestCase
{
    private Account $hotel;
    private Account $distributor;
    private ProjectId $projectId;
    private \DateTimeImmutable $now;
    private InMemoryAccountProjectRepository $accountProjects;
    private InMemoryCreditPurchaseRepository $creditPurchases;
    private InMemoryProjectContributionRepository $contributions;

    protected function setUp(): void
    {
        $this->hotel = AccountMother::fromNameAndId('Hotel', (string) AccountId::generate());
        $this->distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', (string) AccountId::generate());
        $this->projectId = ProjectId::generate();
        $this->now = new \DateTimeImmutable('NOW');
        $this->accountProjects = AccountProjectRepositoryMother::inMemory();
        $this->creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
    }

    public function testTheOwnAccountIsSelectedWhenItsCreditIsEnough(): void
    {
        $this->joinWithCredit($this->hotel->id(), 5.0, CreditPurchasePaymentType::PaidByClient);
        $this->joinWithCredit($this->distributor->id(), 10.0);

        $source = $this->createSUT()->select($this->hotel->id(), $this->projectId, true, null, 3.0, $this->now);

        $this->assertNotNull($source);
        $this->assertTrue($this->hotel->id()->equals($source->accountId));
        $this->assertFalse($source->shared);
        $this->assertSame(5.0, $source->availableTonnes);
    }

    public function testTheDistributorIsSelectedWhenTheOwnCreditIsNotEnough(): void
    {
        $this->joinWithCredit($this->hotel->id(), 1.0, CreditPurchasePaymentType::PaidByClient);
        $this->joinWithCredit($this->distributor->id(), 10.0);

        $source = $this->createSUT()->select($this->hotel->id(), $this->projectId, true, null, 3.0, $this->now);

        $this->assertNotNull($source);
        $this->assertTrue($this->distributor->id()->equals($source->accountId));
        $this->assertTrue($source->shared);
        $this->assertSame(10.0, $source->availableTonnes);
    }

    public function testWithoutSharedCreditAllowedTheDistributorIsIgnored(): void
    {
        $this->joinWithCredit($this->hotel->id(), 1.0, CreditPurchasePaymentType::PaidByClient);
        $this->joinWithCredit($this->distributor->id(), 10.0);

        $sut = $this->createSUT();

        $this->assertNull($sut->select($this->hotel->id(), $this->projectId, false, null, 3.0, $this->now));
        $this->assertCount(1, $sut->candidates($this->hotel->id(), $this->projectId, false, null, $this->now));
    }

    public function testNothingIsSelectedWhenNobodyCoversTheTonnes(): void
    {
        $this->joinWithCredit($this->hotel->id(), 1.0, CreditPurchasePaymentType::PaidByClient);
        $this->joinWithCredit($this->distributor->id(), 2.0);

        $this->assertNull($this->createSUT()->select($this->hotel->id(), $this->projectId, true, null, 3.0, $this->now));
    }

    public function testTheFirstDistributorWithCreditWins(): void
    {
        $older = AccountMother::distributorAccountFromNameAndId('Older', (string) AccountId::generate());
        $newer = AccountMother::distributorAccountFromNameAndId('Newer', (string) AccountId::generate());
        $this->joinWithCredit($older->id(), 1.0);
        $this->joinWithCredit($newer->id(), 10.0);

        $sut = $this->createSUT(
            [$this->hotel, $older, $newer],
            [
                DistributorAssignmentMother::forAccountRegisteredAt($older->id(), $this->hotel->id(), new \DateTimeImmutable('-2 days')),
                DistributorAssignmentMother::forAccountRegisteredAt($newer->id(), $this->hotel->id(), new \DateTimeImmutable('-1 day')),
            ],
        );

        $source = $sut->select($this->hotel->id(), $this->projectId, true, null, 3.0, $this->now);

        $this->assertNotNull($source);
        $this->assertTrue($newer->id()->equals($source->accountId));

        $first = $sut->select($this->hotel->id(), $this->projectId, true, null, 0.5, $this->now);

        $this->assertNotNull($first);
        $this->assertTrue($older->id()->equals($first->accountId));
    }

    public function testADistributorWithoutCompensationIsIgnored(): void
    {
        $this->distributor->changeFeatures(new AccountFeatures(compensation: false));
        $this->joinWithCredit($this->distributor->id(), 10.0);

        $this->assertSame([], $this->createSUT()->candidates($this->hotel->id(), $this->projectId, true, null, $this->now));
    }

    public function testADistributorNotAdheredToTheProjectIsIgnored(): void
    {
        $this->creditPurchases->add(CreditPurchaseMother::validated(
            $this->distributor->id(),
            $this->projectId,
            tonnes: 10.0,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
        ));

        $this->assertSame([], $this->createSUT()->candidates($this->hotel->id(), $this->projectId, true, null, $this->now));
    }

    public function testTheLotsTheDistributorPaidAreNotShared(): void
    {
        $this->joinWithCredit($this->distributor->id(), 10.0, CreditPurchasePaymentType::PaidByClient);

        $this->assertSame([], $this->createSUT()->candidates($this->hotel->id(), $this->projectId, true, null, $this->now));
    }

    public function testAPropertyAssignmentOnlyCountsForThatProperty(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $this->hotel->id());
        $this->joinWithCredit($this->distributor->id(), 10.0);

        $sut = $this->createSUT(
            assignments: [DistributorAssignmentMother::forProperty($this->distributor->id(), $property->id())],
            properties: [$property],
        );

        $this->assertNull($sut->select($this->hotel->id(), $this->projectId, true, null, 1.0, $this->now));
        $this->assertNotNull($sut->select($this->hotel->id(), $this->projectId, true, $property->id(), 1.0, $this->now));
    }

    public function testMaxAvailableTonnesIsTheHighestCandidate(): void
    {
        $this->joinWithCredit($this->hotel->id(), 2.0, CreditPurchasePaymentType::PaidByClient);
        $this->joinWithCredit($this->distributor->id(), 7.0);

        $sut = $this->createSUT();

        $this->assertSame(7.0, $sut->maxAvailableTonnes($this->hotel->id(), $this->projectId, true, null, $this->now));
        $this->assertSame(2.0, $sut->maxAvailableTonnes($this->hotel->id(), $this->projectId, false, null, $this->now));
        $this->assertSame(0.0, $sut->maxAvailableTonnes(AccountId::generate(), $this->projectId, true, null, $this->now));
    }

    public function testSharedProjectIdsAreTheDistributorProjectsWithSharedCredit(): void
    {
        $this->joinWithCredit($this->distributor->id(), 10.0);

        $withoutSharedCredit = ProjectId::generate();
        $this->accountProjects->add(AccountProjectMother::from($this->distributor->id(), $withoutSharedCredit));
        $this->creditPurchases->add(CreditPurchaseMother::validated($this->distributor->id(), $withoutSharedCredit, tonnes: 10.0));

        $projectIds = $this->createSUT()->sharedProjectIds($this->hotel->id(), null, $this->now);

        $this->assertSame([(string) $this->projectId], array_map('strval', $projectIds));
    }

    private function joinWithCredit(
        AccountId $accountId,
        float $tonnes,
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByBioscore,
    ): void {
        $this->accountProjects->add(AccountProjectMother::from($accountId, $this->projectId));
        $this->creditPurchases->add(CreditPurchaseMother::validated($accountId, $this->projectId, tonnes: $tonnes, paymentType: $paymentType));
    }

    /**
     * @param list<Account>|null          $accounts
     * @param list<DistributorAssignment> $assignments
     * @param list<Property>              $properties
     */
    private function createSUT(?array $accounts = null, ?array $assignments = null, array $properties = []): CreditSourceSelector
    {
        return CreditSourceSelectorMother::create(
            $this->accountProjects,
            $this->creditPurchases,
            $this->contributions,
            $accounts ?? [$this->hotel, $this->distributor],
            $assignments ?? [DistributorAssignmentMother::forAccount($this->distributor->id(), $this->hotel->id())],
            $properties,
        );
    }
}
