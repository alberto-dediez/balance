<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Balance;

use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsRequest;
use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsService;
use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsService;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStatus;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationFilter;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class BalanceContext implements Context
{
    private const int MAX_QUEUE_ROUNDS = 10;

    /** @var list<string> */
    private const array LEGACY_REDIRECTS_FILES = [
        'tests/Acceptance/Balance/fixtures/legacy-balance/redirects.txt',
        'tests/Acceptance/Balance/fixtures/legacy-greenhost/redirects.txt',
    ];

    /** @var list<string> */
    private const array TRANSPORTS = [
        'messenger.transport.async_high',
        'messenger.transport.async',
        'messenger.transport.async_low',
    ];

    private readonly AcceptanceClient $client;

    /** @var array<string, string> */
    private array $settleTokens = [];

    public function __construct(KernelBrowser $client)
    {
        $this->client = AcceptanceClient::instance($client);
    }

    #[AfterScenario('@legacy-import')]
    public function removeLegacyRedirectsFile(): void
    {
        foreach (self::LEGACY_REDIRECTS_FILES as $file) {
            $path = $this->projectPath($file);

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    #[Then('account :name should have consultant :consultant')]
    public function accountShouldHaveConsultant(string $name, string $consultant): void
    {
        $consultantAccountId = $this->accountByName($name)->consultantAccountId();

        Assert::assertNotNull($consultantAccountId);
        Assert::assertSame($consultant, $this->getAccountRepository()->find($consultantAccountId)?->name());
    }

    #[Then('account :name should be a consultant')]
    public function accountShouldBeAConsultant(string $name): void
    {
        $account = $this->accountByName($name);

        Assert::assertTrue($account->isConsultant());
        Assert::assertNull($account->consultantAccountId());
    }

    #[Then('account :name should have :count properties')]
    public function accountShouldHaveProperties(string $name, int $count): void
    {
        $properties = $this->getPropertyRepository()->findByFilter(
            new PropertyFilter(accountId: $this->accountByName($name)->id(), limit: 0),
        );

        Assert::assertCount($count, $properties);
    }

    #[Then('property with bioscore id :id should belong to account :name')]
    public function propertyWithBioscoreIdShouldBelongToAccount(string $id, string $name): void
    {
        $property = $this->getPropertyRepository()->findBySourceAndExternalId(
            ExternalSource::Bioscore,
            ExternalId::fromSourceAndId(ExternalSource::Bioscore, $id),
        );

        Assert::assertNotNull($property);
        Assert::assertNotNull($property->accountId());
        Assert::assertTrue($this->accountByName($name)->id()->equals($property->accountId()));
    }

    private function accountByName(string $name): Account
    {
        $account = $this->getAccountRepository()->findByName($name);

        Assert::assertNotNull($account, sprintf('Account "%s" not found', $name));

        return $account;
    }

    #[Then('file :path contains :text')]
    public function fileContains(string $path, string $text): void
    {
        $path = $this->projectPath($path);

        Assert::assertFileExists($path);
        Assert::assertStringContainsString($text, (string) file_get_contents($path));
    }

    #[Then('file :path does not contain :text')]
    public function fileDoesNotContain(string $path, string $text): void
    {
        $path = $this->projectPath($path);

        Assert::assertFileExists($path);
        Assert::assertStringNotContainsString($text, (string) file_get_contents($path));
    }

    private function projectPath(string $path): string
    {
        return $this->client->getKernelBrowser()->getKernel()->getProjectDir() . '/' . $path;
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

    public function getUserRepository(): UserRepository
    {
        return $this->client->getContainer()->get(UserRepository::class);
    }

    public function getAccountRepository(): AccountRepository
    {
        return $this->client->getContainer()->get(AccountRepository::class);
    }

    public function getPropertyRepository(): PropertyRepository
    {
        return $this->client->getContainer()->get(PropertyRepository::class);
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

    #[Given('credit purchase :id for account :accountId and project :projectId requested by :username with :tonnes tonnes exists')]
    public function creditPurchaseRequestedByUserExists(string $id, string $accountId, string $projectId, string $username, string $tonnes): void
    {
        $requester = $this->getUserRepository()->findByUsername($username);

        assert($requester instanceof User);

        $creditPurchase = new CreditPurchase(id: new CreditPurchaseId($id));
        $creditPurchase->request(new AccountId($accountId), new ProjectId($projectId), $requester->id(), (float) $tonnes);

        $this->getCreditPurchaseRepository()->add($creditPurchase);
    }

    #[Given('credit purchase :id is validated with :purchasedTonnes tonnes and reference :reference')]
    public function creditPurchaseIsValidated(string $id, string $purchasedTonnes, string $reference): void
    {
        $creditPurchase = $this->getCreditPurchaseRepository()->find(new CreditPurchaseId($id));

        assert($creditPurchase instanceof CreditPurchase);

        $creditPurchase->validate(
            (float) $purchasedTonnes,
            CreditPurchasePaymentType::PaidByClient,
            $reference,
            null,
            UserId::generate(),
        );

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

    #[When('I go to the detail of lot :lotNumber of serial :reference')]
    public function iGoToTheDetailOfLotOfSerial(int $lotNumber, string $reference): void
    {
        $purchases = $this->getCreditPurchaseRepository()->findByFilter(
            new CreditPurchaseFilter(reference: $reference, limit: 0),
        );

        foreach ($purchases as $creditPurchase) {
            if ($creditPurchase->lotNumber() === $lotNumber) {
                $this->client->request('GET', sprintf('/es/credits/purchases/%s', (string) $creditPurchase->id()));

                return;
            }
        }

        Assert::fail(sprintf('No lot %d found for serial "%s".', $lotNumber, $reference));
    }

    #[Then('project :projectId should be adhered to account :accountId')]
    public function projectShouldBeAdheredToAccount(string $projectId, string $accountId): void
    {
        Assert::assertCount(1, $this->getAccountProjectRepository()->findByFilter(new AccountProjectFilter(
            accountId: new AccountId($accountId),
            projectId: new ProjectId($projectId),
            limit: 0,
        )));
    }

    #[Then('credit purchase :id should have :tonnes free tonnes')]
    public function creditPurchaseShouldHaveFreeTonnes(string $id, string $tonnes): void
    {
        $creditPurchase = $this->getCreditPurchaseRepository()->find(new CreditPurchaseId($id));

        Assert::assertNotNull($creditPurchase);
        Assert::assertEqualsWithDelta((float) $tonnes, $creditPurchase->availableTonnes(), 0.0001);
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

    #[Given('contribution :id of account :accountId for project :projectId with :tonnes tonnes from :origin :originId is pending until :expiresAt')]
    public function pendingContributionFromOriginExists(
        string $id,
        string $accountId,
        string $projectId,
        string $tonnes,
        string $origin,
        string $originId,
        string $expiresAt,
    ): void {
        $this->pendingContributionExists($id, $accountId, $projectId, $tonnes, $expiresAt, ProjectContributionOrigin::from($origin), $originId);
    }

    #[Given('contribution :id of account :accountId for project :projectId with :tonnes tonnes is pending until :expiresAt')]
    public function pendingContributionExists(
        string $id,
        string $accountId,
        string $projectId,
        string $tonnes,
        string $expiresAt,
        ProjectContributionOrigin $origin = ProjectContributionOrigin::PropertyTripCalculation,
        ?string $originId = null,
    ): void {
        $contribution = new ProjectContribution(id: new ProjectContributionId($id));
        $payment = PaymentMother::pending(subjectId: $id);

        $this->getPaymentRepository()->add($payment);

        $contribution->register(
            accountId: new AccountId($accountId),
            projectId: new ProjectId($projectId),
            origin: $origin,
            originId: $originId,
            originName: null,
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
            locale: Locale::Spanish,
            expiresAt: new \DateTimeImmutable($expiresAt),
            paymentId: $payment->id(),
        );

        $this->getProjectContributionRepository()->add($contribution);
    }

    #[When('the credit alerts are checked')]
    public function theCreditAlertsAreChecked(): void
    {
        $this->client->getContainer()->get(CheckCreditAlertsService::class)->__invoke(
            new CheckCreditAlertsRequest(now: new \DateTimeImmutable('NOW')),
            UserMother::systemUser(),
        );
    }

    #[Then('user :username should have :count internal notifications')]
    public function userShouldHaveInternalNotifications(string $username, string $count): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user, sprintf('User "%s" not found', $username));

        $notifications = $this->getNotificationRepository()->findByFilter(new NotificationFilter(
            recipientId: $user->id(),
            channel: NotificationChannel::Internal,
            limit: 0,
        ));

        Assert::assertCount((int) $count, $notifications);
    }

    #[Then('the last internal notification of user :username should contain :text')]
    public function theLastInternalNotificationOfUserShouldContain(string $username, string $text): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user, sprintf('User "%s" not found', $username));

        $notifications = $this->getNotificationRepository()->findByFilter(new NotificationFilter(
            recipientId: $user->id(),
            channel: NotificationChannel::Internal,
            limit: 0,
        ));

        Assert::assertNotEmpty($notifications);
        Assert::assertStringContainsString($text, $notifications[0]->subject() . ' ' . $notifications[0]->body());
    }

    private function getNotificationRepository(): NotificationRepository
    {
        return $this->client->getContainer()->get(NotificationRepository::class);
    }

    #[When('the pending contributions are expired')]
    public function thePendingContributionsAreExpired(): void
    {
        $this->client->getContainer()->get(ExpirePendingContributionsService::class)->__invoke(
            new ExpirePendingContributionsRequest(new \DateTimeImmutable('NOW')),
            UserMother::systemUser(),
        );
    }

    #[When('the queued messages are processed')]
    public function theQueuedMessagesAreProcessed(): void
    {
        $bus = $this->client->getContainer()->get(MessageBusInterface::class);

        for ($round = 0; $round < self::MAX_QUEUE_ROUNDS; ++$round) {
            $messages = [];

            foreach (self::TRANSPORTS as $transportId) {
                $transport = $this->client->getContainer()->get($transportId);

                assert($transport instanceof InMemoryTransport);

                foreach ($transport->getSent() as $envelope) {
                    $messages[] = $envelope->getMessage();
                }

                $transport->reset();
            }

            if ([] === $messages) {
                return;
            }

            foreach ($messages as $message) {
                $bus->dispatch($message, [new ReceivedStamp('async')]);
            }
        }
    }

    #[Then('contribution :id should consume :tonnes tonnes of credit purchase :creditPurchaseId')]
    public function contributionShouldConsumeTonnesOfCreditPurchase(string $id, string $tonnes, string $creditPurchaseId): void
    {
        $contribution = $this->getProjectContributionRepository()->find(new ProjectContributionId($id));

        Assert::assertNotNull($contribution);

        $consumed = 0.0;

        foreach ($contribution->creditAllocations() as $allocation) {
            if ((string) $allocation->creditPurchaseId() === $creditPurchaseId) {
                $consumed += $allocation->tonnes();
            }
        }

        Assert::assertEqualsWithDelta((float) $tonnes, $consumed, 0.0001);
    }

    #[Then('contribution :id should consume the range :startTonnes to :endTonnes of serial :serial')]
    public function contributionShouldConsumeTheRangeOfSerial(
        string $id,
        string $startTonnes,
        string $endTonnes,
        string $serial,
    ): void {
        $contribution = $this->getProjectContributionRepository()->find(new ProjectContributionId($id));

        Assert::assertNotNull($contribution);
        Assert::assertContains(
            ['serial' => $serial, 'startTonnes' => (float) $startTonnes, 'endTonnes' => (float) $endTonnes],
            $contribution->creditLots(),
        );
    }

    #[Then('contribution :id should consume no credit')]
    public function contributionShouldConsumeNoCredit(string $id): void
    {
        $contribution = $this->getProjectContributionRepository()->find(new ProjectContributionId($id));

        Assert::assertNotNull($contribution);
        Assert::assertSame([], $contribution->creditAllocations());
    }

    #[Then('the certificate of contribution :id should be :status')]
    public function theCertificateOfContributionShouldBe(string $id, string $status): void
    {
        $certificate = $this->getContributionCertificateRepository()
            ->findByProjectContributionId(new ProjectContributionId($id))
        ;

        Assert::assertNotNull($certificate);
        Assert::assertSame(ContributionCertificateStatus::from($status), $certificate->status());
    }

    #[Then('the certificate of contribution :id should be a :type document')]
    public function theCertificateOfContributionShouldBeOfType(string $id, string $type): void
    {
        $certificate = $this->getContributionCertificateRepository()
            ->findByProjectContributionId(new ProjectContributionId($id))
        ;

        Assert::assertNotNull($certificate);
        Assert::assertSame(ContributionCertificateType::from($type), $certificate->type());
    }

    #[When('I go to the verification page of the certificate of contribution :id')]
    public function iGoToTheVerificationPageOfTheCertificate(string $id): void
    {
        $certificate = $this->getContributionCertificateRepository()
            ->findByProjectContributionId(new ProjectContributionId($id))
        ;

        Assert::assertNotNull($certificate);

        $this->client->request('GET', '/es/certificate/' . $certificate->verificationToken());
    }

    #[When('I go to the verification page of the certificate of contribution :id using its printed reference')]
    public function iGoToTheVerificationPageOfTheCertificateWithItsCode(string $id): void
    {
        $certificate = $this->getContributionCertificateRepository()
            ->findByProjectContributionId(new ProjectContributionId($id))
        ;

        Assert::assertNotNull($certificate);

        $this->client->request('GET', '/es/certificate/' . $certificate->code());
    }

    #[When('I download the certificate of contribution :id with token :token')]
    public function iDownloadTheCertificateOfContribution(string $id, string $token): void
    {
        $this->client->request(
            'GET',
            sprintf('/es/carbon-footprint/property-trip/%s/contribution/%s/certificate', $token, $id),
        );
    }

    public function getContributionCertificateRepository(): ContributionCertificateRepository
    {
        return $this->client->getContainer()->get(ContributionCertificateRepository::class);
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

    public function getLedgerEntryRepository(): LedgerEntryRepository
    {
        return $this->client->getContainer()->get(LedgerEntryRepository::class);
    }

    #[Given('credit purchase :id is validated with :purchasedTonnes tonnes and reference :reference paid by :paymentType')]
    public function creditPurchaseIsValidatedPaidBy(
        string $id,
        string $purchasedTonnes,
        string $reference,
        string $paymentType,
    ): void {
        $creditPurchase = $this->getCreditPurchaseRepository()->find(new CreditPurchaseId($id));

        assert($creditPurchase instanceof CreditPurchase);

        $creditPurchase->validate(
            (float) $purchasedTonnes,
            CreditPurchasePaymentType::from($paymentType),
            $reference,
            null,
            UserId::generate(),
        );

        $this->getCreditPurchaseRepository()->add($creditPurchase);
    }

    #[When('I settle the :beneficiary :beneficiaryId')]
    public function iSettleTheBeneficiary(string $beneficiary, string $beneficiaryId): void
    {
        $path = sprintf('/es/admin/treasury/settle/%s/%s', $beneficiary, $beneficiaryId);

        $this->client->request('GET', '/es/admin/treasury');

        $link = $this->client->getCrawler()
            ->filter(sprintf('a[href^="%s"]', $path))
            ->extract(['href'])
        ;

        if ([] !== $link) {
            $this->settleTokens[$path] = $link[0];
        }

        $this->client->request('GET', $this->settleTokens[$path] ?? $path);
    }

    #[Then('the ledger balance of account :accountId should be :amount')]
    public function theLedgerBalanceOfAccountShouldBe(string $accountId, string $amount): void
    {
        $this->assertLedgerBalance(LedgerBeneficiary::Account, $accountId, $amount);
    }

    #[Then('the ledger balance of project :projectId should be :amount')]
    public function theLedgerBalanceOfProjectShouldBe(string $projectId, string $amount): void
    {
        $this->assertLedgerBalance(LedgerBeneficiary::Project, $projectId, $amount);
    }

    #[Then('the bioscore ledger balance should be :amount')]
    public function theBioscoreLedgerBalanceShouldBe(string $amount): void
    {
        $this->assertLedgerBalance(LedgerBeneficiary::Bioscore, null, $amount);
    }

    #[Then('the unassigned ledger balance should be :amount')]
    public function theUnassignedLedgerBalanceShouldBe(string $amount): void
    {
        $this->assertLedgerBalance(LedgerBeneficiary::Unassigned, null, $amount);
    }

    #[Then('contribution :id should have :count ledger entries')]
    public function contributionShouldHaveLedgerEntries(string $id, int $count): void
    {
        Assert::assertCount(
            $count,
            $this->getLedgerEntryRepository()->findByFilter(
                new LedgerEntryFilter(projectContributionId: new ProjectContributionId($id), limit: 0),
            ),
        );
    }

    #[Then('the ledger entries of contribution :id should add up to its total amount')]
    public function theLedgerEntriesOfContributionShouldAddUp(string $id): void
    {
        $contribution = $this->getProjectContributionRepository()->find(new ProjectContributionId($id));

        assert($contribution instanceof ProjectContribution);

        Assert::assertEqualsWithDelta(
            $contribution->totalAmount(),
            $this->getLedgerEntryRepository()->recordedAmountFor($contribution->id()),
            0.01,
        );
    }

    private function assertLedgerBalance(LedgerBeneficiary $beneficiary, ?string $beneficiaryId, string $amount): void
    {
        Assert::assertEqualsWithDelta(
            (float) $amount,
            $this->getLedgerEntryRepository()->balanceFor($beneficiary, $beneficiaryId)->accruedAmount(),
            0.01,
        );
    }
}
