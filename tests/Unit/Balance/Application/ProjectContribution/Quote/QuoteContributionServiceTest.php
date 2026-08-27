<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Quote;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionService;
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
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class QuoteContributionServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
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

    private function request(
        ?float $co2eqKg = 2000.0,
        ?float $freeAmount = null,
        string $country = 'ESP',
        bool $isCompany = false,
        ?string $vatNumber = null,
    ): QuoteContributionRequest {
        return new QuoteContributionRequest(
            accountId: $this->accountId,
            projectId: $this->projectId,
            co2eqKg: $co2eqKg,
            freeAmount: $freeAmount,
            contributorCountry: $country,
            contributorIsCompany: $isCompany,
            contributorVatNumber: $vatNumber,
        );
    }

    private function createSUT(
        ProjectType $projectType = ProjectType::EmissionCompensation,
        ?float $pricePerTonne = 20.0,
        ?float $costPerTonne = 15.0,
        float $purchasedTonnes = 10.0,
        bool $adhered = true,
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

        return new QuoteContributionService(
            $projects,
            $accountProjects,
            new CreditAvailability($creditPurchases, $this->contributions),
            new ContributionCalculator(new VatCalculator(
                new VatNumberFormatValidator(),
                new InMemoryVatCountryProvider([
                    new VatCountry('ESP', 21.0, true),
                    new VatCountry('FRA', 20.0, true),
                ]),
                homeCountry: 'ESP',
            )),
        );
    }
}
