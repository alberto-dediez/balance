<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Quote;

use App\Balance\Application\ProjectContribution\Quote\QuoteContributionRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatCountry;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use App\Payments\Infrastructure\Vat\InMemory\InMemoryVatCountryProvider;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Application\CreditPurchase\Balance\CreditSourceSelectorMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class QuoteContributionServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;
    private AccountId $distributorAccountId;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->distributorAccountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
    }

    public function testQuotesTheEmissionsToOffset(): void
    {
        $response = $this->createSUT()($this->request(co2eqKg: 2000.0));

        $this->assertSame(2.0, $response->amounts->tonnes);
        $this->assertSame(40.0, $response->amounts->taxableAmount);
        $this->assertSame(10.0, $response->amounts->managementFeeAmount);
        $this->assertSame(30.0, $response->amounts->contributionAmount);
        $this->assertSame(8.4, $response->amounts->vatAmount);
        $this->assertSame(48.4, $response->amounts->totalAmount);
        $this->assertSame(VatExemptionReason::Standard, $response->amounts->vatReason);
    }

    public function testQuotesAFreeAmount(): void
    {
        $response = $this->createSUT()($this->request(co2eqKg: null, freeAmount: 50.0));

        $this->assertTrue($response->amounts->freeAmount);
        $this->assertNull($response->amounts->co2eqKg);
        $this->assertSame(2.066, $response->amounts->tonnes);
        $this->assertSame(41.32, $response->amounts->taxableAmount);
        $this->assertSame(50.0, $response->amounts->totalAmount);
    }

    public function testAnOffsettingProjectIsLimitedByTheCreditLeft(): void
    {
        $this->contributions->add(
            ProjectContributionMother::paid($this->accountId, $this->projectId, tonnes: 4.0),
        );

        $response = $this->createSUT(purchasedTonnes: 10.0)($this->request());

        $this->assertTrue($response->limitedByCredit);
        $this->assertSame(6.0, $response->availableTonnes);
        $this->assertSame(15.0, $response->amounts->costPerTonne);
    }

    public function testASocialProjectIsNotLimitedByCredit(): void
    {
        $response = $this->createSUT(projectType: ProjectType::SocialProject, purchasedTonnes: 0.0)(
            $this->request(),
        );

        $this->assertFalse($response->limitedByCredit);
        $this->assertSame(\INF, $response->availableTonnes);
        $this->assertSame(15.0, $response->amounts->costPerTonne);
    }

    public function testAProjectPricedAtItsCostChargesNoFee(): void
    {
        $response = $this->createSUT(costPerTonne: 20.0)($this->request());

        $this->assertSame(0.0, $response->amounts->managementFeeAmount);
        $this->assertSame(40.0, $response->amounts->contributionAmount);
    }

    public function testTheVatOfTheContributorCountryIsApplied(): void
    {
        $response = $this->createSUT()($this->request(country: 'FRA'));

        $this->assertSame(20.0, $response->amounts->vatRate);
        $this->assertSame(VatExemptionReason::OssDestination, $response->amounts->vatReason);
        $this->assertSame(48.0, $response->amounts->totalAmount);
    }

    public function testAnEuCompanyWithAValidVatNumberIsReverseCharged(): void
    {
        $response = $this->createSUT()(
            $this->request(country: 'FRA', isCompany: true, vatNumber: 'FR12345678901'),
        );

        $this->assertSame(0.0, $response->amounts->vatAmount);
        $this->assertSame(VatExemptionReason::ReverseCharge, $response->amounts->vatReason);
        $this->assertSame(40.0, $response->amounts->totalAmount);
    }

    public function testRejectsAProjectTheAccountHasNotAdhered(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(adhered: false)($this->request());
    }

    public function testRejectsAnAccountWithoutCompensation(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(compensation: false)($this->request());
    }

    public function testRejectsAProjectWithoutAPrice(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(pricePerTonne: null)($this->request());
    }

    public function testRejectsAProjectWithoutACost(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(costPerTonne: null)($this->request());
    }

    public function testRejectsAnUnknownProject(): void
    {
        $sut = $this->createSUT();

        $this->expectException(ProjectNotContributableException::class);

        $sut(new QuoteContributionRequest(
            accountId: $this->accountId,
            projectId: ProjectId::generate(),
            co2eqKg: 2000.0,
            contributorCountry: 'ESP',
        ));
    }

    public function testRejectsARequestWithNeitherEmissionsNorAmount(): void
    {
        $sut = $this->createSUT();

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request(co2eqKg: null));
    }

    public function testTheDistributorCreditBacksTheQuoteWhenTheOwnCreditIsNotEnough(): void
    {
        $response = $this->createSUT(purchasedTonnes: 1.0, sharedTonnes: 8.0)($this->request(allowSharedCredit: true));

        $this->assertTrue($response->limitedByCredit);
        $this->assertNotNull($response->creditAccountId);
        $this->assertTrue($this->distributorAccountId->equals($response->creditAccountId));
        $this->assertSame(8.0, $response->availableTonnes);
    }

    public function testTheOwnCreditBacksTheQuoteWhenItIsEnough(): void
    {
        $response = $this->createSUT(purchasedTonnes: 3.0, sharedTonnes: 8.0)($this->request(allowSharedCredit: true));

        $this->assertNotNull($response->creditAccountId);
        $this->assertTrue($this->accountId->equals($response->creditAccountId));
        $this->assertSame(8.0, $response->availableTonnes);
    }

    public function testWithoutSharedCreditAllowedTheOwnAccountBacksTheQuote(): void
    {
        $response = $this->createSUT(purchasedTonnes: 1.0, sharedTonnes: 8.0)($this->request());

        $this->assertNotNull($response->creditAccountId);
        $this->assertTrue($this->accountId->equals($response->creditAccountId));
        $this->assertSame(1.0, $response->availableTonnes);
    }

    public function testANotAdheredAccountIsQuotedWithTheDistributorCredit(): void
    {
        $response = $this->createSUT(purchasedTonnes: 0.0, adhered: false, sharedTonnes: 8.0)($this->request(allowSharedCredit: true));

        $this->assertNotNull($response->creditAccountId);
        $this->assertTrue($this->distributorAccountId->equals($response->creditAccountId));
    }

    public function testRejectsAProjectWithoutAdhesionNorSharedCredit(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(purchasedTonnes: 0.0, adhered: false)($this->request(allowSharedCredit: true));
    }

    public function testRejectsAProjectWithoutAdhesionWhenSharedCreditIsNotAllowed(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(purchasedTonnes: 0.0, adhered: false, sharedTonnes: 8.0)($this->request());
    }

    private function request(
        ?float $co2eqKg = 2000.0,
        ?float $freeAmount = null,
        string $country = 'ESP',
        bool $isCompany = false,
        ?string $vatNumber = null,
        bool $allowSharedCredit = false,
    ): QuoteContributionRequest {
        return new QuoteContributionRequest(
            accountId: $this->accountId,
            projectId: $this->projectId,
            co2eqKg: $co2eqKg,
            freeAmount: $freeAmount,
            contributorCountry: $country,
            contributorIsCompany: $isCompany,
            contributorVatNumber: $vatNumber,
            allowSharedCredit: $allowSharedCredit,
        );
    }

    private function createSUT(
        ProjectType $projectType = ProjectType::EmissionCompensation,
        ?float $pricePerTonne = 20.0,
        ?float $costPerTonne = 15.0,
        float $purchasedTonnes = 10.0,
        bool $adhered = true,
        bool $compensation = true,
        float $sharedTonnes = 0.0,
    ): QuoteContributionService {
        $projects = ProjectRepositoryMother::inMemory();
        $projects->add(ProjectMother::contributable(
            projectType: $projectType,
            pricePerTonne: $pricePerTonne ?? 0.0,
            costPerTonne: $costPerTonne,
            id: $this->projectId,
        ));

        $accountProjects = AccountProjectRepositoryMother::inMemory();

        if ($adhered) {
            $accountProjects->add(AccountProjectMother::from($this->accountId, $this->projectId));
        }

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();

        if ($purchasedTonnes > 0.0) {
            $creditPurchases->add(
                CreditPurchaseMother::validated($this->accountId, $this->projectId, tonnes: $purchasedTonnes),
            );
        }

        $assignments = [];

        if ($sharedTonnes > 0.0) {
            $accountProjects->add(AccountProjectMother::from($this->distributorAccountId, $this->projectId));
            $creditPurchases->add(CreditPurchaseMother::validated(
                $this->distributorAccountId,
                $this->projectId,
                tonnes: $sharedTonnes,
                paymentType: CreditPurchasePaymentType::PaidByBioscore,
            ));
            $assignments[] = DistributorAssignmentMother::forAccount($this->distributorAccountId, $this->accountId);
        }

        $accounts = [
            AccountMother::withFeatures('account', (string) $this->accountId, new AccountFeatures(compensation: $compensation)),
            AccountMother::distributorAccountFromNameAndId('Greenhost', (string) $this->distributorAccountId),
        ];

        return new QuoteContributionService(
            $projects,
            $accountProjects,
            new ContributionCalculator(new VatCalculator(
                new VatNumberFormatValidator(),
                new InMemoryVatCountryProvider([
                    new VatCountry('ESP', 21.0, true),
                    new VatCountry('FRA', 20.0, true),
                ]),
                homeCountry: 'ESP',
            )),
            AccountFeaturesPolicyMother::create($accounts),
            CreditSourceSelectorMother::create($accountProjects, $creditPurchases, $this->contributions, $accounts, $assignments),
        );
    }
}
