<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Notification\CreditPurchaseRequestedNotifier;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseBillingMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuoteMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseRequestedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesEveryAdministratorWithProjectInfo(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));
        $userRepository->add(UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin));
        $userRepository->add(UserMother::fromUsernameAndRole('owner', Role::Owner));

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseRequestedNotifier($notifier, $projectRepository, $userRepository);

        CreditPurchaseMother::pending(projectId: $project->id());

        $notifications = $notifier->notifications();

        $this->assertCount(4, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertSame($project->name(), $notifications[0]->params['projectName']);
    }

    public function testAdministratorsReceiveTheRequestedTotal(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $notifier = new SpySystemNotifier();

        new CreditPurchaseRequestedNotifier($notifier, ProjectRepositoryMother::inMemory(), $userRepository);

        CreditPurchaseMother::pendingWithBilling(AccountId::generate(), quote: CreditPurchaseQuoteMother::standard());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(181.5, $notifications[0]->params['requestedTotal']);
        $this->assertSame(181.5, $notifications[1]->params['requestedTotal']);
    }

    public function testTheRequesterReceivesASingleEmailWithTheSummary(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));
        $userRepository->add($requester);

        $project = ProjectMother::random();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseRequestedNotifier($notifier, $projectRepository, $userRepository);

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(
            AccountId::generate(),
            $project->id(),
            $requester->id(),
            10.0,
            CreditPurchaseQuoteMother::standard(),
            CreditPurchaseBillingMother::random(),
        );

        $requesterNotifications = $this->notificationsFor($notifier, $requester->id());

        $this->assertCount(3, $notifier->notifications());
        $this->assertCount(1, $requesterNotifications);

        $notification = $requesterNotifications[0];

        $this->assertSame(NotificationChannel::Email, $notification->channel);
        $this->assertSame('@balance/notification/credit-purchase-requested-requester.email.html.twig', $notification->template);
        $this->assertSame((string) $creditPurchase->id(), $notification->params['creditPurchaseId']);
        $this->assertSame($project->name(), $notification->params['projectName']);
        $this->assertSame(10.0, $notification->params['requestedTonnes']);
        $this->assertSame(150.0, $notification->params['taxableAmount']);
        $this->assertSame(21.0, $notification->params['vatRate']);
        $this->assertSame(31.5, $notification->params['vatAmount']);
        $this->assertSame('standard', $notification->params['vatReason']);
        $this->assertSame(181.5, $notification->params['totalAmount']);
        $this->assertSame('Mi Empresa SL', $notification->params['billingLegalName']);
        $this->assertSame('B12345678', $notification->params['billingTaxId']);
        $this->assertSame('Calle Mayor 1', $notification->params['billingStreet']);
        $this->assertSame('28001', $notification->params['billingPostalCode']);
        $this->assertSame('Madrid', $notification->params['billingCity']);
        $this->assertSame('ESP', $notification->params['billingCountry']);
        $this->assertSame('facturas@example.com', $notification->params['billingEmail']);
    }

    public function testTheRequesterEmailHasNoAmountsWithoutQuote(): void
    {
        $requester = UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, AccountId::generate());

        $userRepository = new InMemoryUserRepository();
        $userRepository->add($requester);

        $notifier = new SpySystemNotifier();

        new CreditPurchaseRequestedNotifier($notifier, ProjectRepositoryMother::inMemory(), $userRepository);

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request(
            AccountId::generate(),
            ProjectId::generate(),
            $requester->id(),
            10.0,
            null,
            CreditPurchaseBillingMother::random(),
        );

        $notifications = $notifier->notifications();

        $this->assertCount(1, $notifications);
        $this->assertNull($notifications[0]->params['taxableAmount']);
        $this->assertNull($notifications[0]->params['vatRate']);
        $this->assertNull($notifications[0]->params['vatAmount']);
        $this->assertNull($notifications[0]->params['vatReason']);
        $this->assertNull($notifications[0]->params['totalAmount']);
        $this->assertSame('Mi Empresa SL', $notifications[0]->params['billingLegalName']);
    }

    public function testAnUnknownRequesterIsNotNotifiedButAdministratorsAre(): void
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        $notifier = new SpySystemNotifier();

        new CreditPurchaseRequestedNotifier($notifier, ProjectRepositoryMother::inMemory(), $userRepository);

        CreditPurchaseMother::pendingWithBilling(AccountId::generate(), quote: CreditPurchaseQuoteMother::standard());

        $notifications = $notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame('@balance/notification/credit-purchase-requested.notification.html.twig', $notifications[0]->template);
        $this->assertSame('@balance/notification/credit-purchase-requested.email.html.twig', $notifications[1]->template);
    }

    /** @return list<SystemNotification> */
    private function notificationsFor(SpySystemNotifier $notifier, UserId $userId): array
    {
        return array_values(array_filter(
            $notifier->notifications(),
            static fn(SystemNotification $notification): bool => $notification->recipientId->equals($userId),
        ));
    }
}
