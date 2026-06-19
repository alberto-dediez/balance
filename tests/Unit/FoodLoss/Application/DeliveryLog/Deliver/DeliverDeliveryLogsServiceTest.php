<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DeliveryLog\Deliver;

use App\FoodLoss\Application\DeliveryLog\Deliver\DeliverDeliveryLogsRequest;
use App\FoodLoss\Application\DeliveryLog\Deliver\DeliverDeliveryLogsService;
use App\FoodLoss\Application\DeliveryLog\Deliver\MixedRecipientOrganizationsException;
use App\FoodLoss\Application\DeliveryLog\Deliver\SignerDataMissingException;
use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogSignatureStarted;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogsPdfGenerator;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestResponse;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class DeliverDeliveryLogsServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        LocaleGetter::setLocale(null);

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testDeliversDirectly(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $deliveryLog = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
        );
        $deliveryLogRepository->add($deliveryLog);

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$deliveryLog->id()];
        $request->withSignature = false;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
        );

        $response = $sut($request, $user);

        $this->assertEquals(1, $response->deliveredCount);
        $this->assertNull($response->signatureRequestId);

        $persisted = $deliveryLogRepository->find($deliveryLog->id());
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isDelivered());
    }

    public function testDeliversWithSignatureUsingOrganizationContactData(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $org = RecipientOrganizationMother::fromContact('Juan García', 'juan@example.com', '+34600000000');
        $recipientOrganizationRepository->add($org);

        $deliveryLog = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $deliveryLogRepository->add($deliveryLog);

        $signatureRequestId = SignatureRequestId::generate();
        $signatureRequestService = $this->mockSignatureRequestService($signatureRequestId);

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$deliveryLog->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');
        $request->otpRequired = false;

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            signatureRequestService: $signatureRequestService,
        );

        $response = $sut($request, $user);

        $this->assertEquals(1, $response->deliveredCount);
        $this->assertNotNull($response->signatureRequestId);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(DeliveryLogSignatureStarted::class, $lastEvent);
    }

    public function testThrowsWhenMixedOrganizationsOnSignatureRequest(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $log1 = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: 'org-1',
        );
        $log2 = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-2',
            accountId: 'account-id-test',
            recipientOrganizationId: 'org-2',
        );
        $deliveryLogRepository->add($log1);
        $deliveryLogRepository->add($log2);

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$log1->id(), $log2->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
        );

        $this->expectException(MixedRecipientOrganizationsException::class);

        $sut($request, $user);
    }

    public function testThrowsWhenEmailChannelButContactHasNoEmail(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $org = RecipientOrganizationMother::fromContact('Juan García', '', '+34600000000');
        $recipientOrganizationRepository->add($org);

        $log = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $deliveryLogRepository->add($log);

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$log->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
        );

        $this->expectException(SignerDataMissingException::class);

        $sut($request, $user);
    }

    public function testDeliversMultipleLogsOfSameOrganizationWithSignature(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $org = RecipientOrganizationMother::fromContact('Ana López', 'ana@example.com', '+34611111111');
        $recipientOrganizationRepository->add($org);

        $log1 = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $log2 = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Vegetables,
            id: 'log-2',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $deliveryLogRepository->add($log1);
        $deliveryLogRepository->add($log2);

        $signatureRequestId = SignatureRequestId::generate();
        $signatureRequestService = $this->mockSignatureRequestService($signatureRequestId);

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$log1->id(), $log2->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            signatureRequestService: $signatureRequestService,
        );

        $response = $sut($request, $user);

        $this->assertEquals(2, $response->deliveredCount);
        $this->assertNotNull($response->signatureRequestId);
    }

    public function testUsesContactLocaleWhenAvailable(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $org = RecipientOrganizationMother::fromContact('Juan García', 'juan@example.com', '+34600000000', Locale::English);
        $recipientOrganizationRepository->add($org);

        $log = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $deliveryLogRepository->add($log);

        $capturedLocale = null;
        $signatureRequestService = $this->createMock(RegisterSignatureRequestService::class);
        $signatureRequestService->method('__invoke')
            ->willReturnCallback(function (RegisterSignatureRequestRequest $req) use (&$capturedLocale) {
                $capturedLocale = $req->signerLocale;

                return new RegisterSignatureRequestResponse(
                    signatureRequestId: SignatureRequestId::generate(),
                    tokenPlainValue: 'token',
                    status: SignatureRequestStatus::Pending,
                );
            });

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$log->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            signatureRequestService: $signatureRequestService,
        );

        $sut($request, $user);

        $this->assertEquals(Locale::English, $capturedLocale);
    }

    public function testFallsBackToInterfaceLocaleWhenContactHasNone(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        LocaleGetter::setLocale(Locale::French);

        $org = RecipientOrganizationMother::fromContact('Juan García', 'juan@example.com', '+34600000000');
        $recipientOrganizationRepository->add($org);

        $log = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $deliveryLogRepository->add($log);

        $capturedLocale = null;
        $signatureRequestService = $this->createMock(RegisterSignatureRequestService::class);
        $signatureRequestService->method('__invoke')
            ->willReturnCallback(function (RegisterSignatureRequestRequest $req) use (&$capturedLocale) {
                $capturedLocale = $req->signerLocale;

                return new RegisterSignatureRequestResponse(
                    signatureRequestId: SignatureRequestId::generate(),
                    tokenPlainValue: 'token',
                    status: SignatureRequestStatus::Pending,
                );
            });

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$log->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            signatureRequestService: $signatureRequestService,
        );

        $sut($request, $user);

        $this->assertEquals(Locale::French, $capturedLocale);
    }

    public function testFallsBackToSpanishWhenNoLocaleAvailable(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationRepository = RecipientOrganizationRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $org = RecipientOrganizationMother::fromContact('Juan García', 'juan@example.com', '+34600000000');
        $recipientOrganizationRepository->add($org);

        $log = DeliveryLogMother::from(
            productType: \App\FoodLoss\Domain\Model\DeliveryLog\FoodType::Fruit,
            id: 'log-1',
            accountId: 'account-id-test',
            recipientOrganizationId: (string) $org->id(),
        );
        $deliveryLogRepository->add($log);

        $capturedLocale = null;
        $signatureRequestService = $this->createMock(RegisterSignatureRequestService::class);
        $signatureRequestService->method('__invoke')
            ->willReturnCallback(function (RegisterSignatureRequestRequest $req) use (&$capturedLocale) {
                $capturedLocale = $req->signerLocale;

                return new RegisterSignatureRequestResponse(
                    signatureRequestId: SignatureRequestId::generate(),
                    tokenPlainValue: 'token',
                    status: SignatureRequestStatus::Pending,
                );
            });

        $request = new DeliverDeliveryLogsRequest();
        $request->deliveryLogIds = [$log->id()];
        $request->withSignature = true;
        $request->deliveredAt = new \DateTimeImmutable('2026-06-18');

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            signatureRequestService: $signatureRequestService,
        );

        $sut($request, $user);

        $this->assertEquals(Locale::Spanish, $capturedLocale);
    }

    private function mockSignatureRequestService(SignatureRequestId $signatureRequestId): RegisterSignatureRequestService
    {
        $mock = $this->createMock(RegisterSignatureRequestService::class);
        $mock->method('__invoke')->willReturn(new RegisterSignatureRequestResponse(
            signatureRequestId: $signatureRequestId,
            tokenPlainValue: 'token',
            status: SignatureRequestStatus::Pending,
        ));

        return $mock;
    }

    private function createSUT(
        mixed $deliveryLogRepository,
        mixed $recipientOrganizationRepository,
        ?RegisterSignatureRequestService $signatureRequestService = null,
    ): DeliverDeliveryLogsService {
        $pdfGenerator = $this->createMock(DeliveryLogsPdfGenerator::class);
        $pdfGenerator->method('generate')->willReturn('/tmp/test.pdf');

        $signatureRequestService ??= $this->createMock(RegisterSignatureRequestService::class);

        return new DeliverDeliveryLogsService(
            deliveryLogRepository: $deliveryLogRepository,
            recipientOrganizationRepository: $recipientOrganizationRepository,
            authorizer: new DeliveryLogAuthorizer(),
            deliveryLogsPdfGenerator: $pdfGenerator,
            registerSignatureRequestService: $signatureRequestService,
        );
    }
}
