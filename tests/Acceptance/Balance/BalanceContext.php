<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Balance;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsService;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class BalanceContext implements Context
{
    private readonly AcceptanceClient $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = AcceptanceClient::instance($client);
    }

    public function getProjectRepository(): ProjectRepository
    {
        return $this->client->getContainer()->get(ProjectRepository::class);
    }

    public function getAccountProjectRepository(): AccountProjectRepository
    {
        return $this->client->getContainer()->get(AccountProjectRepository::class);
    }

    public function getCreditPurchaseRepository(): CreditPurchaseRepository
    {
        return $this->client->getContainer()->get(CreditPurchaseRepository::class);
    }

    #[Given('project :name with id :id exists')]
    public function projectWithIdExists(string $name, string $id): void
    {
        $project = ProjectMother::fromNameAndId($name, $id);

        $this->getProjectRepository()->add($project);
    }

    #[Given('project :name with id :id and location :location exists')]
    public function projectWithIdAndLocationExists(string $name, string $id, string $location): void
    {
        $project = ProjectMother::fromNameAndIdAndLocation($name, $id, $location);

        $this->getProjectRepository()->add($project);
    }

    #[Given('project :id is adhered to account :accountId')]
    public function projectIsAdheredToAccount(string $id, string $accountId): void
    {
        $accountProject = new AccountProject(id: AccountProjectId::generate());
        $accountProject->select(new AccountId($accountId), new ProjectId($id));

        $this->getAccountProjectRepository()->add($accountProject);
    }

    #[Given('credit purchase :id for account :accountId and project :projectId requesting :tonnes tonnes exists')]
    public function creditPurchaseExists(string $id, string $accountId, string $projectId, string $tonnes): void
    {
        $creditPurchase = new CreditPurchase(id: new CreditPurchaseId($id));
        $creditPurchase->request(new AccountId($accountId), new ProjectId($projectId), UserId::generate(), (float) $tonnes);

        $this->getCreditPurchaseRepository()->add($creditPurchase);
    }

    #[Given('credit purchase :id is validated with :purchasedTonnes tonnes and reference :reference')]
    public function creditPurchaseIsValidated(string $id, string $purchasedTonnes, string $reference): void
    {
        $creditPurchase = $this->getCreditPurchaseRepository()->find(new CreditPurchaseId($id));

        assert($creditPurchase instanceof CreditPurchase);

        $creditPurchase->validate((float) $purchasedTonnes, $reference, null, UserId::generate());

        $this->getCreditPurchaseRepository()->add($creditPurchase);
    }

    public function getProjectContributionRepository(): ProjectContributionRepository
    {
        return $this->client->getContainer()->get(ProjectContributionRepository::class);
    }

    public function getPaymentRepository(): PaymentRepository
    {
        return $this->client->getContainer()->get(PaymentRepository::class);
    }

    #[Given('project :name with id :id and price per tonne :price exists')]
    public function projectWithIdAndPriceExists(string $name, string $id, string $price): void
    {
        $this->getProjectRepository()->add(ProjectMother::from(
            name: $name,
            id: $id,
            pricePerTonne: (float) $price,
            costPerTonne: 15.0,
        ));
    }

    #[Given('project :name with id :id and price per tonne :price and cost per tonne :cost exists')]
    public function projectWithIdAndPriceAndCostExists(string $name, string $id, string $price, string $cost): void
    {
        $this->getProjectRepository()->add(ProjectMother::from(
            name: $name,
            id: $id,
            pricePerTonne: (float) $price,
            costPerTonne: (float) $cost,
        ));
    }

    #[Given('social project :name with id :id and price per tonne :price exists')]
    public function socialProjectWithIdAndPriceExists(string $name, string $id, string $price): void
    {
        $this->getProjectRepository()->add(ProjectMother::from(
            name: $name,
            id: $id,
            projectType: ProjectType::SocialProject,
            pricePerTonne: (float) $price,
            costPerTonne: (float) $price,
        ));
    }

    #[When('I go to the details of project :id in the current compensation flow')]
    public function iGoToTheDetailsOfProject(string $id): void
    {
        $path = preg_replace(
            '#/compensate/[^/]+/details$#',
            '/compensate/' . $id . '/details',
            $this->client->getRequest()->getRequestUri(),
        );

        Assert::assertIsString($path);

        $this->client->request('GET', $path);
    }

    #[When('the gateway notifies that the payment of contribution :id succeeded')]
    public function theGatewayNotifiesThePaymentSucceeded(string $id): void
    {
        $this->sendStripeWebhook('checkout.session.completed', $id, ['payment_intent' => 'pi_test_1']);
    }

    #[When('the gateway notifies that the payment of contribution :id expired')]
    public function theGatewayNotifiesThePaymentExpired(string $id): void
    {
        $this->sendStripeWebhook('checkout.session.expired', $id);
    }

    #[When('the gateway notifies that the payment of contribution :id failed')]
    public function theGatewayNotifiesThePaymentFailed(string $id): void
    {
        $this->sendStripeWebhook('payment_intent.payment_failed', $id, [
            'last_payment_error' => ['message' => 'card_declined'],
        ]);
    }

    #[Then('project :id should have a price per tonne of :price')]
    public function projectShouldHaveAPricePerTonneOf(string $id, string $price): void
    {
        $project = $this->getProjectRepository()->find(new ProjectId($id));

        Assert::assertNotNull($project);
        Assert::assertEqualsWithDelta((float) $price, $project->pricePerTonne(), 0.0001);
    }

    #[Then('I am redirected to the payment gateway')]
    public function iAmRedirectedToThePaymentGateway(): void
    {
        $response = $this->client->getResponse();

        Assert::assertTrue($response->isRedirect());
        Assert::assertStringStartsWith(
            'https://checkout.stripe.test/',
            (string) $response->headers->get('Location'),
        );
    }

    #[When('the gateway notifies that the payment of the only contribution succeeded')]
    public function theGatewayNotifiesThePaymentOfTheOnlyContributionSucceeded(): void
    {
        $contributions = $this->getProjectContributionRepository()->findByFilter(new ProjectContributionFilter(limit: 0));

        Assert::assertCount(1, $contributions);

        $this->sendStripeWebhook('checkout.session.completed', (string) $contributions[0]->id(), [
            'payment_intent' => 'pi_test_1',
        ]);
    }

    #[Then('contribution :id should be :status')]
    public function contributionShouldBe(string $id, string $status): void
    {
        $contribution = $this->getProjectContributionRepository()->find(new ProjectContributionId($id));

        Assert::assertNotNull($contribution);
        Assert::assertSame(ProjectContributionStatus::from($status), $contribution->status());
    }

    #[Then('account :accountId should have :tonnes available tonnes in project :projectId')]
    public function accountShouldHaveAvailableTonnes(string $accountId, string $tonnes, string $projectId): void
    {
        $availability = $this->client->getContainer()->get(CreditAvailability::class);
        $balance = $availability->for(new AccountId($accountId), new ProjectId($projectId));

        Assert::assertEqualsWithDelta((float) $tonnes, $balance->availableTonnes(), 0.0001);
    }

    #[Then('there should be :count contributions')]
    public function thereShouldBeContributions(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->getProjectContributionRepository()->findByFilter(new ProjectContributionFilter(limit: 0)),
        );
    }

    #[Then('the only contribution should be :status')]
    public function theOnlyContributionShouldBe(string $status): void
    {
        $contributions = $this->getProjectContributionRepository()->findByFilter(new ProjectContributionFilter(limit: 0));

        Assert::assertCount(1, $contributions);
        Assert::assertSame(ProjectContributionStatus::from($status), $contributions[0]->status());
    }

    #[Given('contribution :id of account :accountId for project :projectId with :tonnes tonnes is pending until :expiresAt')]
    public function pendingContributionExists(
        string $id,
        string $accountId,
        string $projectId,
        string $tonnes,
        string $expiresAt,
    ): void {
        $contribution = new ProjectContribution(id: new ProjectContributionId($id));
        $payment = PaymentMother::pending(subjectId: $id);

        $this->getPaymentRepository()->add($payment);

        $contribution->register(
            accountId: new AccountId($accountId),
            projectId: new ProjectId($projectId),
            origin: ProjectContributionOrigin::PropertyTripCalculation,
            originId: null,
            co2eqKg: (float) $tonnes * 1000,
            tonnes: (float) $tonnes,
            pricePerTonne: 20.0,
            costPerTonne: 15.0,
            freeAmount: false,
            contributionAmount: (float) $tonnes * 15.0,
            managementFeeAmount: (float) $tonnes * 5.0,
            taxableAmount: (float) $tonnes * 20.0,
            vatRate: 21.0,
            vatAmount: round((float) $tonnes * 20.0 * 0.21, 2),
            totalAmount: round((float) $tonnes * 20.0 * 1.21, 2),
            vatReason: VatExemptionReason::Standard,
            currency: 'EUR',
            contributorName: 'Guest',
            contributorEmail: 'guest@example.com',
            contributorCountry: 'ES',
            contributorIsCompany: false,
            contributorCompanyName: null,
            contributorVatNumber: null,
            expiresAt: new \DateTimeImmutable($expiresAt),
            paymentId: $payment->id(),
        );

        $this->getProjectContributionRepository()->add($contribution);
    }

    #[When('the pending contributions are expired')]
    public function thePendingContributionsAreExpired(): void
    {
        $this->client->getContainer()->get(ExpirePendingContributionsService::class)->__invoke(
            new ExpirePendingContributionsRequest(new \DateTimeImmutable('NOW')),
            UserMother::systemUser(),
        );
    }

    /** @param array<string, mixed> $extra */
    private function sendStripeWebhook(string $type, string $contributionId, array $extra = []): void
    {
        $contribution = $this->getProjectContributionRepository()->find(new ProjectContributionId($contributionId));

        Assert::assertNotNull($contribution);

        $paymentId = $contribution->paymentId();

        Assert::assertInstanceOf(PaymentId::class, $paymentId);

        $payload = json_encode([
            'id' => 'evt_test_1',
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => array_merge([
                    'id' => 'cs_test_1',
                    'object' => 'checkout.session',
                    'metadata' => ['payment_id' => (string) $paymentId],
                ], $extra),
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_dummy');

        $this->client->request(
            'POST',
            '/webhooks/stripe',
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => sprintf('t=%d,v1=%s', $timestamp, $signature),
            ],
            $payload,
        );
    }
}
