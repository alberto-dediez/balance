<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Quote;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class QuoteOwnerCompensationServiceTest extends TestCase
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

    public function testValuesTheEmissionsAtTheCostOfTheOwnLots(): void
    {
        $response = $this->createSUT()($this->request(co2eqKg: 2000.0));

        $this->assertSame(2.0, $response->tonnes);
        $this->assertSame(30.0, $response->contributionAmount);
        $this->assertSame(10.0, $response->ownAvailableTonnes);
        $this->assertFalse($response->exceedsCredit);
    }

    public function testReportsWhenTheEmissionsExceedTheOwnCredit(): void
    {
        $response = $this->createSUT(purchasedTonnes: 1.0)($this->request(co2eqKg: 2000.0));

        $this->assertTrue($response->exceedsCredit);
        $this->assertSame(1.0, $response->ownAvailableTonnes);
    }

    public function testLotsPaidByBioscoreDoNotCountAsOwnCredit(): void
    {
        $response = $this->createSUT(paymentType: CreditPurchasePaymentType::PaidByBioscore)(
            $this->request(co2eqKg: 2000.0),
        );

        $this->assertSame(0.0, $response->ownAvailableTonnes);
        $this->assertTrue($response->exceedsCredit);
    }

    public function testASocialProjectCannotBeCompensatedWithOwnCredit(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(projectType: ProjectType::SocialProject)($this->request(co2eqKg: 2000.0));
    }

    public function testAProjectWithoutACostCannotBeCompensated(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(costPerTonne: null)($this->request(co2eqKg: 2000.0));
    }

    public function testAProjectTheAccountHasNotAdheredCannotBeCompensated(): void
    {
        $this->expectException(ProjectNotContributableException::class);

        $this->createSUT(adhered: false)($this->request(co2eqKg: 2000.0));
    }

    public function testTheEmissionsMustBeGreaterThanZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request(co2eqKg: 0.0));
    }

    private function request(float $co2eqKg): QuoteOwnerCompensationRequest
    {
        return new QuoteOwnerCompensationRequest(
            accountId: $this->accountId,
            projectId: $this->projectId,
            co2eqKg: $co2eqKg,
        );
    }

    private function createSUT(
        ProjectType $projectType = ProjectType::EmissionCompensation,
        ?float $costPerTonne = 15.0,
        float $purchasedTonnes = 10.0,
        bool $adhered = true,
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByClient,
    ): QuoteOwnerCompensationService {
        $projects = ProjectRepositoryMother::inMemory();
        $projects->add(ProjectMother::contributable(
            projectType: $projectType,
            costPerTonne: $costPerTonne,
            id: $this->projectId,
        ));

        $accountProjects = AccountProjectRepositoryMother::inMemory();

        if ($adhered) {
            $accountProjects->add(AccountProjectMother::from($this->accountId, $this->projectId));
        }

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchases->add(CreditPurchaseMother::validated(
            $this->accountId,
            $this->projectId,
            tonnes: $purchasedTonnes,
            paymentType: $paymentType,
        ));

        return new QuoteOwnerCompensationService(
            $projects,
            $accountProjects,
            new CreditAvailability($creditPurchases, $this->contributions),
        );
    }
}
