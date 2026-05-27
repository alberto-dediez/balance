<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\Surplus;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogService;
use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogService;
use App\FoodLoss\Application\DeliveryLog\Update\UpdateDeliveryLogService;
use App\FoodLoss\Domain\Event\Surplus\RegisterDeliveryLogOnSurplusHumanDonationAssessedSubscriber;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRemoved;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\Surplus\SurplusHumanDonationAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RegisterDeliveryLogOnSurplusHumanDonationAssessedSubscriberTest extends TestCase
{
    private const string CRITICAL_POINT_ID = '00000000-0000-0000-0000-000000000001';

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToSurplusHumanDonationAssessed(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new SurplusHumanDonationAssessed(
            surplusId: SurplusId::generate(),
            canBeHumanDonated: true,
            reasonId: null,
        )));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new DeliveryLogRemoved(DeliveryLogId::generate())));
    }

    public function testHandleRegistersDeliveryLogWhenAllConditionsMet(): void
    {
        $accountId = new AccountId('account-id-test');
        $recipientOrganizationId = RecipientOrganizationId::generate();
        $donationDate = new \DateTimeImmutable('2026-05-27');

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $surplusRepository->add($surplus);

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPointRepository->add(CriticalPointMother::fromNameAndId('cp', self::CRITICAL_POINT_ID));

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT(
            surplusRepository: $surplusRepository,
            criticalPointRepository: $criticalPointRepository,
            deliveryLogRepository: $deliveryLogRepository,
            user: $user,
        );

        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: $surplus->id(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: $donationDate,
            batch: 'BATCH-01',
            recipientOrganizationId: $recipientOrganizationId,
            createDeliveryLog: true,
        ));

        $deliveryLogs = $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0));

        $this->assertCount(1, $deliveryLogs);
        $deliveryLog = $deliveryLogs[0];
        $this->assertSame($surplus->quantity(), $deliveryLog->quantity());
        $this->assertSame($surplus->unit(), $deliveryLog->unit());
        $this->assertSame($surplus->foodType(), $deliveryLog->productType());
        $this->assertNotNull($deliveryLog->recipientOrganizationId());
        $this->assertTrue($deliveryLog->recipientOrganizationId()->equals($recipientOrganizationId));
        $this->assertNotNull($deliveryLog->accountId());
        $this->assertTrue($deliveryLog->accountId()->equals($accountId));
        $this->assertNotNull($deliveryLog->surplusId());
        $this->assertTrue($deliveryLog->surplusId()->equals($surplus->id()));
    }

    public function testHandleUpdatesExistingDeliveryLogWhenSurplusIsReassessed(): void
    {
        $accountId = new AccountId('account-id-test');
        $recipientOrganizationId = RecipientOrganizationId::generate();
        $newDonationDate = new \DateTimeImmutable('2026-06-01');

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $surplusRepository->add($surplus);

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPointRepository->add(CriticalPointMother::fromNameAndId('cp', self::CRITICAL_POINT_ID));

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $existingDeliveryLog = DeliveryLogMother::withSurplusId($surplus->id(), $accountId);
        $deliveryLogRepository->add($existingDeliveryLog);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT(
            surplusRepository: $surplusRepository,
            criticalPointRepository: $criticalPointRepository,
            deliveryLogRepository: $deliveryLogRepository,
            user: $user,
        );

        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: $surplus->id(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: $newDonationDate,
            batch: 'BATCH-02',
            recipientOrganizationId: $recipientOrganizationId,
            createDeliveryLog: true,
        ));

        $deliveryLogs = $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0));

        $this->assertCount(1, $deliveryLogs);
        $this->assertTrue($deliveryLogs[0]->id()->equals($existingDeliveryLog->id()));
        $this->assertSame($newDonationDate->format('Y-m-d'), $deliveryLogs[0]->date()->format('Y-m-d'));
        $this->assertSame('BATCH-02', $deliveryLogs[0]->batch());
    }

    public function testHandleRemovesExistingDeliveryLogWhenNoLongerDonatable(): void
    {
        $accountId = new AccountId('account-id-test');
        $surplusId = SurplusId::generate();

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $existingDeliveryLog = DeliveryLogMother::withSurplusId($surplusId, $accountId);
        $deliveryLogRepository->add($existingDeliveryLog);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            user: $user,
        );

        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: $surplusId,
            canBeHumanDonated: false,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: RecipientOrganizationId::generate(),
            createDeliveryLog: true,
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleRemovesExistingDeliveryLogWhenCreateDeliveryLogSetToFalse(): void
    {
        $accountId = new AccountId('account-id-test');
        $surplusId = SurplusId::generate();

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $existingDeliveryLog = DeliveryLogMother::withSurplusId($surplusId, $accountId);
        $deliveryLogRepository->add($existingDeliveryLog);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT(
            deliveryLogRepository: $deliveryLogRepository,
            user: $user,
        );

        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: $surplusId,
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: RecipientOrganizationId::generate(),
            createDeliveryLog: false,
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleDoesNothingWhenCreateDeliveryLogIsFalse(): void
    {
        $accountId = new AccountId('account-id-test');
        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $surplusRepository->add($surplus);

        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPointRepository->add(CriticalPointMother::fromNameAndId('cp', self::CRITICAL_POINT_ID));

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT(
            surplusRepository: $surplusRepository,
            criticalPointRepository: $criticalPointRepository,
            deliveryLogRepository: $deliveryLogRepository,
            user: $user,
        );

        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: $surplus->id(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: RecipientOrganizationId::generate(),
            createDeliveryLog: false,
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleDoesNothingWhenCannotBeHumanDonated(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $sut = $this->createSUT(deliveryLogRepository: $deliveryLogRepository);
        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: SurplusId::generate(),
            canBeHumanDonated: false,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: RecipientOrganizationId::generate(),
            createDeliveryLog: true,
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleDoesNothingWhenDonationDateIsNull(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $sut = $this->createSUT(deliveryLogRepository: $deliveryLogRepository);
        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: SurplusId::generate(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: null,
            recipientOrganizationId: RecipientOrganizationId::generate(),
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleDoesNothingWhenRecipientOrganizationIsNull(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $sut = $this->createSUT(deliveryLogRepository: $deliveryLogRepository);
        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: SurplusId::generate(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: null,
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleDoesNothingWhenSurplusNotFound(): void
    {
        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $sut = $this->createSUT(deliveryLogRepository: $deliveryLogRepository);
        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: SurplusId::generate(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: RecipientOrganizationId::generate(),
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testHandleDoesNothingWhenCriticalPointNotFound(): void
    {
        $accountId = new AccountId('account-id-test');
        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $surplusRepository->add($surplus);

        $deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();

        $sut = $this->createSUT(
            surplusRepository: $surplusRepository,
            deliveryLogRepository: $deliveryLogRepository,
        );

        $sut->handle(new SurplusHumanDonationAssessed(
            surplusId: $surplus->id(),
            canBeHumanDonated: true,
            reasonId: null,
            donationDate: new \DateTimeImmutable('2026-05-27'),
            recipientOrganizationId: RecipientOrganizationId::generate(),
        ));

        $this->assertCount(0, $deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    private function createSUT(
        ?SurplusRepository $surplusRepository = null,
        ?CriticalPointRepository $criticalPointRepository = null,
        ?DeliveryLogRepository $deliveryLogRepository = null,
        ?User $user = null,
    ): RegisterDeliveryLogOnSurplusHumanDonationAssessedSubscriber {
        $accountId = new AccountId('account-id-test');
        $repository = $deliveryLogRepository ?? DeliveryLogRepositoryMother::inMemory();
        $authorizer = new DeliveryLogAuthorizer();

        return new RegisterDeliveryLogOnSurplusHumanDonationAssessedSubscriber(
            surplusRepository: $surplusRepository ?? SurplusRepositoryMother::inMemory(),
            criticalPointRepository: $criticalPointRepository ?? CriticalPointRepositoryMother::inMemory(),
            deliveryLogRepository: $repository,
            registerDeliveryLogService: new RegisterDeliveryLogService(
                repository: $repository,
                authorizer: $authorizer,
            ),
            updateDeliveryLogService: new UpdateDeliveryLogService(
                repository: $repository,
                authorizer: $authorizer,
            ),
            removeDeliveryLogService: new RemoveDeliveryLogService(
                repository: $repository,
                authorizer: $authorizer,
            ),
            loggedUser: new FakeLoggedUser(
                $user ?? UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
            ),
        );
    }
}
