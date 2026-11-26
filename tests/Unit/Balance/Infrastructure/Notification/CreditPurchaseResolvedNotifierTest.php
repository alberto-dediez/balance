<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Infrastructure\Notification\CreditPurchaseResolvedNotifier;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseBillingMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuoteMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseResolvedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesRequesterOnFullValidationWithProjectInfo(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->validate(10.0, CreditPurchasePaymentType::PaidByClient, 'REF-1', null, UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertTrue($notifications[0]->recipientId->equals($requester->id()));
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testNotifiesRequesterOnPartialValidationWithProjectInfo(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->validate(6.0, CreditPurchasePaymentType::PaidByClient, 'REF-2', null, UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertStringContainsString('parcial', $notifications[0]->subject);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testNotifiesRequesterOnRejection(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->reject('Sin disponibilidad', UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertTrue($notifications[0]->recipientId->equals($requester->id()));
    }

    public function testFullValidationOfARequestWithVatSendsTheTotalAndTheBreakdown(): void
    {
        $notifications = $this->validateRequestWithVat(10.0);

        $this->assertCount(2, $notifications);

        foreach ($notifications as $notification) {
            $this->assertSame(181.5, $notification->params['amount']);
            $this->assertSame(150.0, $notification->params['taxableAmount']);
            $this->assertSame(21.0, $notification->params['vatRate']);
            $this->assertSame(31.5, $notification->params['vatAmount']);
            $this->assertSame('standard', $notification->params['vatReason']);
        }
    }

    public function testPartialValidationOfARequestWithVatSendsThePurchasedTotalAndTheBreakdown(): void
    {
        $notifications = $this->validateRequestWithVat(6.0);

        $this->assertCount(2, $notifications);
        $this->assertStringContainsString('parcial', $notifications[0]->subject);
        $this->assertSame(108.9, $notifications[0]->params['amount']);
        $this->assertSame(90.0, $notifications[0]->params['taxableAmount']);
        $this->assertSame(21.0, $notifications[0]->params['vatRate']);
        $this->assertSame(18.9, $notifications[0]->params['vatAmount']);
        $this->assertSame('standard', $notifications[0]->params['vatReason']);
    }

    public function testValidationOfARequestWithoutVatKeepsTheCurrentAmount(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::contributable(costPerTonne: 15.0);
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(AccountId::generate(), $project->id(), $requester->id(), 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->validate(10.0, CreditPurchasePaymentType::PaidByClient, 'REF-1', null, UserId::generate());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(150.0, $notifications[0]->params['amount']);
        $this->assertNull($notifications[0]->params['taxableAmount']);
        $this->assertNull($notifications[0]->params['vatRate']);
        $this->assertNull($notifications[0]->params['vatAmount']);
        $this->assertNull($notifications[0]->params['vatReason']);
    }

    /** @return list<SystemNotification> */
    private function validateRequestWithVat(float $purchasedTonnes): array
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $project = ProjectMother::contributable(costPerTonne: 15.0);
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(
            AccountId::generate(),
            $project->id(),
            $requester->id(),
            10.0,
            CreditPurchaseQuoteMother::standard(),
            CreditPurchaseBillingMother::random(),
        );
        $creditPurchaseRepository->add($creditPurchase);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseResolvedNotifier($notifier, $creditPurchaseRepository, $projectRepository, $userRepository);

        $creditPurchase->validate($purchasedTonnes, CreditPurchasePaymentType::PaidByClient, 'REF-1', null, UserId::generate());

        return $notifier->notifications();
    }
}
